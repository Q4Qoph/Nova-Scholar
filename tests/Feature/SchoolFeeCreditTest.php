<?php

namespace Tests\Feature;

use App\Filament\School\Pages\FeeOperations;
use App\Models\Enrolment;
use App\Models\FeeAdjustment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolFeeCredit;
use App\Services\Schools\ReviewSchoolFeeCredit;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolFeeCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_request_is_idempotent_and_only_approval_reduces_the_charge_balance(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $charge = $this->postedCharge($school, $requester, 10000);
        $payload = ['adjustment_key' => 'credit-request-001', 'amount_minor' => 3000, 'reason' => 'Duplicate charge line'];

        $request = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, $payload);
        $replayedRequest = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, $payload);

        $this->assertSame($request->id, $replayedRequest->id);
        $this->assertSame('pending', $request->status);
        $this->assertSame(10000, $charge->fresh()->outstandingMinor());
        $this->assertDatabaseCount('fee_adjustments', 1);

        $approved = app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $request->id, 'approve', null);
        $replayedApproval = app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $request->id, 'approve', null);

        $this->assertSame($request->id, $replayedApproval->id);
        $this->assertSame('approved', $approved->status);
        $this->assertSame($reviewer->id, $approved->reviewed_by_user_id);
        $this->assertSame(7000, $charge->fresh()->outstandingMinor());
        $this->assertDatabaseCount('audit_events', 2);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'fee_adjustment.requested', 'auditable_id' => $request->id]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'fee_adjustment.approved', 'auditable_id' => $request->id]);
    }

    public function test_credit_request_key_cannot_be_reused_with_changed_details(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $charge = $this->postedCharge($school, $admin, 10000);
        $payload = ['adjustment_key' => 'credit-request-replay', 'amount_minor' => 3000, 'reason' => 'Original reason'];
        app(RequestSchoolFeeCredit::class)->handle($admin, $school, $charge->id, $payload);

        try {
            app(RequestSchoolFeeCredit::class)->handle($admin, $school, $charge->id, [...$payload, 'amount_minor' => 4000]);
            $this->fail('The same request key with changed details must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame('This credit request key was already used for different details.', $exception->errors()['adjustment_key'][0]);
        }

        $this->assertDatabaseCount('fee_adjustments', 1);
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_credit_request_rejects_a_charge_from_another_school(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$otherSchool, $otherAdmin] = $this->schoolWithAdmins();
        $foreignCharge = $this->postedCharge($otherSchool, $otherAdmin, 10000);

        try {
            app(RequestSchoolFeeCredit::class)->handle($admin, $school, $foreignCharge->id, [
                'adjustment_key' => 'foreign-charge-credit',
                'amount_minor' => 1000,
                'reason' => 'Foreign charge',
            ]);
            $this->fail('A charge from another school must not be found in this school context.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('fee_adjustments', 0);
        }
    }

    public function test_credit_requester_cannot_review_and_non_admin_cannot_review(): void
    {
        [$school, $requester] = $this->schoolWithAdmins();
        $teacher = $this->addSchoolRole($school, SchoolRole::Teacher);
        $charge = $this->postedCharge($school, $requester, 10000);
        $adjustment = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-self-review',
            'amount_minor' => 1000,
            'reason' => 'Correct a duplicate item',
        ]);

        try {
            app(ReviewSchoolFeeCredit::class)->handle($requester, $school, $adjustment->id, 'approve', null);
            $this->fail('The requester cannot review their own credit request.');
        } catch (ValidationException $exception) {
            $this->assertSame('A different school administrator must review this request.', $exception->errors()['adjustment_id'][0]);
        }

        try {
            app(ReviewSchoolFeeCredit::class)->handle($teacher, $school, $adjustment->id, 'approve', null);
            $this->fail('A teacher cannot review a fee credit request.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('fee_adjustments', ['id' => $adjustment->id, 'status' => 'pending']);
        }
    }

    public function test_credit_cannot_exceed_charge_balance_after_receipt_allocations(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $charge = $this->postedCharge($school, $admin, 10000);
        $receipt = app(RecordSchoolReceipt::class)->handle($admin, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => '23cabcb9-0786-4787-93e8-a3a7472236d1',
            'currency' => 'KES',
            'amount_minor' => 4000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
        app(AllocateSchoolReceipt::class)->handle($admin, $school, $receipt->id, $charge->id, 4000, 'credit-balance-allocation');

        try {
            app(RequestSchoolFeeCredit::class)->handle($admin, $school, $charge->id, [
                'adjustment_key' => 'credit-over-current-balance',
                'amount_minor' => 6001,
                'reason' => 'Above the open balance',
            ]);
            $this->fail('The credit must not exceed the unpaid receivable.');
        } catch (ValidationException $exception) {
            $this->assertSame('The credit exceeds the charge’s outstanding balance.', $exception->errors()['amount_minor'][0]);
        }

        $this->assertSame(6000, $charge->fresh()->outstandingMinor());
        $this->assertDatabaseCount('fee_adjustments', 0);
    }

    public function test_reviewer_rechecks_balance_before_approving_a_pending_credit(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $charge = $this->postedCharge($school, $requester, 10000);
        $adjustment = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-stale-balance',
            'amount_minor' => 7000,
            'reason' => 'Credit request before allocation',
        ]);
        $receipt = app(RecordSchoolReceipt::class)->handle($requester, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => '3c4e6074-f72d-45fa-8c94-48fd4b6a9622',
            'currency' => 'KES',
            'amount_minor' => 4000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
        app(AllocateSchoolReceipt::class)->handle($requester, $school, $receipt->id, $charge->id, 4000, 'stale-credit-allocation');

        try {
            app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $adjustment->id, 'approve', null);
            $this->fail('An approval must not exceed the balance remaining at review time.');
        } catch (ValidationException $exception) {
            $this->assertSame('The charge balance changed. Recheck the outstanding amount before approving.', $exception->errors()['adjustment_id'][0]);
        }

        $this->assertDatabaseHas('fee_adjustments', ['id' => $adjustment->id, 'status' => 'pending']);
        $this->assertSame(6000, $charge->fresh()->outstandingMinor());
    }

    public function test_rejected_credit_requires_a_reason_and_does_not_change_the_balance(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $charge = $this->postedCharge($school, $requester, 10000);
        $adjustment = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-rejection',
            'amount_minor' => 2000,
            'reason' => 'Review requested credit',
        ]);

        try {
            app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $adjustment->id, 'reject', null);
            $this->fail('A rejection must include a reason.');
        } catch (ValidationException $exception) {
            $this->assertSame('A reason is required when rejecting a credit request.', $exception->errors()['review_note'][0]);
        }

        $rejected = app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $adjustment->id, 'reject', 'Insufficient supporting detail.');

        $this->assertSame('rejected', $rejected->status);
        $this->assertSame(10000, $charge->fresh()->outstandingMinor());
        $this->assertDatabaseHas('audit_events', ['event_type' => 'fee_adjustment.rejected', 'auditable_id' => $adjustment->id]);
    }

    public function test_receipt_allocation_cannot_exceed_balance_after_credit_approval(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $charge = $this->postedCharge($school, $requester, 10000);
        $adjustment = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-before-allocation',
            'amount_minor' => 6000,
            'reason' => 'Reduce the unpaid amount',
        ]);
        app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $adjustment->id, 'approve', null);
        $receipt = app(RecordSchoolReceipt::class)->handle($requester, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'da79b948-ccf0-41c8-a10f-6c75097452e8',
            'currency' => 'KES',
            'amount_minor' => 5000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);

        try {
            app(AllocateSchoolReceipt::class)->handle($requester, $school, $receipt->id, $charge->id, 5000, 'allocation-after-credit');
            $this->fail('Receipt allocation must respect the approved credit.');
        } catch (ValidationException $exception) {
            $this->assertSame('The allocation exceeds the outstanding charge balance.', $exception->errors()['amount_minor'][0]);
        }

        $this->assertDatabaseCount('fee_receipt_allocations', 0);
        $this->assertSame(4000, $charge->fresh()->outstandingMinor());
    }

    public function test_school_admin_can_request_and_review_credit_in_the_fee_workspace(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $charge = $this->postedCharge($school, $requester, 10000);
        $this->actingAs($requester);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('requestSchoolFeeCredit', [
                'fee_charge_id' => $charge->id,
                'amount_minor' => 2500,
                'reason' => 'Duplicate fee line',
                'adjustment_key' => 'filament-credit-001',
            ])
            ->assertHasNoErrors();

        $adjustment = FeeAdjustment::query()->where('school_id', $school->id)->sole();
        $this->assertSame('pending', $adjustment->status);

        $this->actingAs($reviewer);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('reviewSchoolFeeCredit', [
                'fee_adjustment_id' => $adjustment->id,
                'decision' => 'approve',
                'review_note' => null,
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fee_adjustments', ['id' => $adjustment->id, 'status' => 'approved', 'reviewed_by_user_id' => $reviewer->id]);
        $this->actingAs($reviewer)
            ->get('/school/'.$school->slug.'/fee-operations')
            ->assertOk()
            ->assertSee('KES 25.00 approved credits')
            ->assertSee('KES 75.00 outstanding');
    }

    /** @return array{0: School, 1: User}|array{0: School, 1: User, 2: User} */
    private function schoolWithAdmins(int $adminCount = 1): array
    {
        $school = School::factory()->create();
        $admins = [];

        for ($index = 0; $index < $adminCount; $index++) {
            $admins[] = $this->addSchoolRole($school, SchoolRole::SchoolAdmin);
        }

        return [$school, ...$admins];
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

    private function postedCharge(School $school, User $creator, int $amountMinor): FeeCharge
    {
        $learner = Enrolment::factory()->for($school)->create();
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => $amountMinor]);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'created_by_user_id' => $creator->id,
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => $amountMinor,
            'posted_at' => now(),
        ]);

        return FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($learner)->create([
            'amount_minor' => $amountMinor,
            'status' => 'posted',
        ]);
    }
}
