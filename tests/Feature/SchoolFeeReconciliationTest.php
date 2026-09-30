<?php

namespace Tests\Feature;

use App\Filament\School\Pages\FeeReconciliation;
use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\SchoolRefund;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\CompleteSchoolReceiptRefund;
use App\Services\Schools\ReconcileSchoolFees;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolFeeCredit;
use App\Services\Schools\RequestSchoolReceiptRefund;
use App\Services\Schools\ReviewSchoolFeeCredit;
use App\Services\Schools\ReviewSchoolReceiptRefund;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolFeeReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_calculates_settled_balances_and_reports_pending_refunds_separately(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $enrolment = $this->enrolment($school, 'REC-001');
        $charge = $this->postedCharge($school, $enrolment, $requester, 10000);
        $credit = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'reconcile-credit-001',
            'amount_minor' => 1000,
            'reason' => 'Approved fee correction',
        ]);
        app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $credit->id, 'approve', null);

        $receipt = $this->recordReceipt($school, $requester, 12000);
        app(AllocateSchoolReceipt::class)->handle($requester, $school, $receipt->id, $charge->id, 4000, 'reconcile-allocation-001');
        $approvedRefund = $this->requestRefund($requester, $school, $receipt, 'reconcile-refund-approved', 2000);
        app(ReviewSchoolReceiptRefund::class)->handle($reviewer, $school, $approvedRefund->id, 'approve', null);
        $paidRefund = $this->requestRefund($requester, $school, $receipt, 'reconcile-refund-paid', 1000);
        app(ReviewSchoolReceiptRefund::class)->handle($reviewer, $school, $paidRefund->id, 'approve', null);
        app(CompleteSchoolReceiptRefund::class)->handle($reviewer, $school, $paidRefund->id, 'reconcile-paid-001', null);
        $this->requestRefund($requester, $school, $receipt, 'reconcile-refund-pending', 500);

        $result = app(ReconcileSchoolFees::class)->handle($school);
        $totals = $result['currencies']->get('KES');

        $this->assertSame(10000, $totals['posted_charges_minor']);
        $this->assertSame(1000, $totals['approved_credits_minor']);
        $this->assertSame(4000, $totals['net_allocations_minor']);
        $this->assertSame(5000, $totals['outstanding_receivables_minor']);
        $this->assertSame(0, $totals['charge_difference_minor']);
        $this->assertSame(12000, $totals['verified_receipts_minor']);
        $this->assertSame(4000, $totals['receipt_net_allocations_minor']);
        $this->assertSame(5000, $totals['available_receipt_credit_minor']);
        $this->assertSame(2000, $totals['approved_refunds_minor']);
        $this->assertSame(1000, $totals['paid_refunds_minor']);
        $this->assertSame(0, $totals['receipt_difference_minor']);
        $this->assertSame(500, $totals['pending_or_rejected_refunds_minor']);
        $this->assertSame(0, $totals['unverified_receipts_minor']);
        $this->assertSame('Africa/Nairobi', $result['generated_at']->getTimezone()->getName());
    }

    public function test_reconciliation_flags_inconsistent_source_rows_without_mutating_them(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $enrolment = $this->enrolment($school, 'REC-002');
        $charge = $this->postedCharge($school, $enrolment, $admin, 10000);
        $receipt = $this->recordReceipt($school, $admin, 10000);
        $allocation = app(AllocateSchoolReceipt::class)->handle($admin, $school, $receipt->id, $charge->id, 8000, 'reconcile-allocation-002');
        $allocation->forceFill(['amount_minor' => 11000])->save();

        $beforeCharge = $charge->fresh()->amount_minor;
        $beforeReceipt = $receipt->fresh()->amount_minor;
        $beforeAllocation = $allocation->fresh()->amount_minor;
        $totals = app(ReconcileSchoolFees::class)->handle($school)['currencies']->get('KES');

        $this->assertSame(-1000, $totals['charge_difference_minor']);
        $this->assertSame(-1000, $totals['receipt_difference_minor']);
        $this->assertSame($beforeCharge, $charge->fresh()->amount_minor);
        $this->assertSame($beforeReceipt, $receipt->fresh()->amount_minor);
        $this->assertSame($beforeAllocation, $allocation->fresh()->amount_minor);
    }

    public function test_fee_reconciliation_page_is_available_to_school_admins_only(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $teacher = $this->addSchoolRole($school, SchoolRole::Teacher);

        $this->actingAs($admin);
        Filament::setTenant($school);
        Livewire::test(FeeReconciliation::class)
            ->assertSee('Fee reconciliation')
            ->assertSee('No posted charges or receipts are available to reconcile.');

        $this->actingAs($teacher);
        Filament::setTenant($school);
        $this->assertFalse(FeeReconciliation::canAccess());
    }

    /** @return array<int, School|User> */
    private function schoolWithAdmins(int $adminCount = 1): array
    {
        $school = School::factory()->create();
        $users = [$school];

        for ($index = 0; $index < $adminCount; $index++) {
            $users[] = $this->addSchoolRole($school, SchoolRole::SchoolAdmin);
        }

        return $users;
    }

    private function addSchoolRole(School $school, SchoolRole $role): User
    {
        $user = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => $role]);

        return $user;
    }

    private function enrolment(School $school, string $admissionNumber): Enrolment
    {
        return Enrolment::factory()->for($school)->for(
            LearnerProfile::factory()->state(['first_name' => 'Nia', 'last_name' => $admissionNumber]),
            'learnerProfile',
        )->create(['admission_number' => $admissionNumber]);
    }

    private function postedCharge(School $school, Enrolment $enrolment, User $creator, int $amountMinor): FeeCharge
    {
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => $amountMinor]);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'created_by_user_id' => $creator->id,
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => $amountMinor,
            'posted_at' => now(),
        ]);

        return FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($enrolment)->create([
            'amount_minor' => $amountMinor,
            'status' => 'posted',
            'charged_on' => today()->toDateString(),
        ]);
    }

    private function recordReceipt(School $school, User $actor, int $amountMinor): SchoolReceipt
    {
        return app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'reconcile-receipt-'.str()->uuid(),
            'currency' => 'KES',
            'amount_minor' => $amountMinor,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
    }

    private function requestRefund(User $requester, School $school, SchoolReceipt $receipt, string $key, int $amountMinor): SchoolRefund
    {
        return app(RequestSchoolReceiptRefund::class)->handle($requester, $school, $receipt->id, [
            'refund_key' => $key,
            'amount_minor' => $amountMinor,
            'refund_method' => 'cash',
            'reason' => 'Reconciliation test refund',
        ]);
    }
}
