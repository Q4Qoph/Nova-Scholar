<?php

namespace Tests\Feature;

use App\Filament\School\Pages\FeeOperations;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\SchoolRefund;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\CompleteSchoolReceiptRefund;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolReceiptRefund;
use App\Services\Schools\ReviewSchoolReceiptRefund;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolReceiptRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_refund_moves_through_request_review_and_payout_without_changing_source_records(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $receipt = $this->recordReceipt($school, $requester, 10000);
        $requestService = app(RequestSchoolReceiptRefund::class);
        $payload = [
            'refund_key' => 'cash-refund-lifecycle',
            'amount_minor' => 4000,
            'refund_method' => 'cash',
            'reason' => 'Duplicate cash receipt',
        ];

        $refund = $requestService->handle($requester, $school, $receipt->id, $payload);
        $replayedRequest = $requestService->handle($requester, $school, $receipt->id, $payload);

        $this->assertSame($refund->id, $replayedRequest->id);
        $this->assertSame('pending', $refund->status);
        $this->assertSame(10000, $receipt->fresh()->availableMinor());

        try {
            app(ReviewSchoolReceiptRefund::class)->handle($requester, $school, $refund->id, 'approve', null);
            $this->fail('The refund requester cannot approve their own request.');
        } catch (ValidationException $exception) {
            $this->assertSame('A different school administrator must review this request.', $exception->errors()['refund_id'][0]);
        }

        $reviewService = app(ReviewSchoolReceiptRefund::class);
        $approvedRefund = $reviewService->handle($reviewer, $school, $refund->id, 'approve', null);
        $replayedApproval = $reviewService->handle($reviewer, $school, $refund->id, 'approve', null);

        $this->assertSame($refund->id, $replayedApproval->id);
        $this->assertSame('approved', $approvedRefund->status);
        $this->assertSame(6000, $receipt->fresh()->availableMinor());

        $completionService = app(CompleteSchoolReceiptRefund::class);
        $paidRefund = $completionService->handle($reviewer, $school, $refund->id, 'cash-refund-paid', null);
        $replayedPayout = $completionService->handle($reviewer, $school, $refund->id, 'cash-refund-paid', null);

        $this->assertSame($refund->id, $replayedPayout->id);
        $this->assertSame('paid', $paidRefund->status);
        $this->assertSame($reviewer->id, $paidRefund->completed_by_user_id);
        $this->assertSame(6000, $receipt->fresh()->availableMinor());
        $this->assertSame(10000, $receipt->fresh()->amount_minor);
        $this->assertDatabaseCount('school_refunds', 1);
        $this->assertDatabaseCount('audit_events', 4);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'school_refund.paid', 'auditable_id' => $refund->id]);
    }

    public function test_refund_requests_reject_overdrawn_or_foreign_receipts_and_non_admins(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$otherSchool, $otherAdmin] = $this->schoolWithAdmins();
        $teacher = $this->addSchoolRole($school, SchoolRole::Teacher);
        $receipt = $this->recordReceipt($school, $admin, 1000);
        $foreignReceipt = $this->recordReceipt($otherSchool, $otherAdmin, 1000);
        $requestService = app(RequestSchoolReceiptRefund::class);

        try {
            $requestService->handle($admin, $school, $receipt->id, [
                'refund_key' => 'refund-overdrawn',
                'amount_minor' => 1001,
                'refund_method' => 'cash',
                'reason' => 'Above available balance',
            ]);
            $this->fail('A refund cannot exceed the available receipt balance.');
        } catch (ValidationException $exception) {
            $this->assertSame('The refund exceeds the unallocated receipt balance.', $exception->errors()['amount_minor'][0]);
        }

        try {
            $requestService->handle($teacher, $school, $receipt->id, [
                'refund_key' => 'teacher-refund-request',
                'amount_minor' => 500,
                'refund_method' => 'cash',
                'reason' => 'Unauthorized refund',
            ]);
            $this->fail('Teachers cannot request a school refund.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('school_refunds', 0);
        }

        try {
            $requestService->handle($admin, $school, $foreignReceipt->id, [
                'refund_key' => 'foreign-refund-request',
                'amount_minor' => 500,
                'refund_method' => 'cash',
                'reason' => 'Foreign receipt',
            ]);
            $this->fail('A school cannot refund a receipt owned by another school.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('school_refunds', 0);
        }
    }

    public function test_bank_payout_requires_a_unique_reference_and_review_rejection_releases_the_reservation(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $receipt = $this->recordReceipt($school, $requester, 10000);
        $requestService = app(RequestSchoolReceiptRefund::class);
        $reviewService = app(ReviewSchoolReceiptRefund::class);
        $refund = $requestService->handle($requester, $school, $receipt->id, [
            'refund_key' => 'bank-refund-rejected',
            'amount_minor' => 3500,
            'refund_method' => 'bank',
            'reason' => 'Incorrect allocation',
        ]);

        try {
            $reviewService->handle($reviewer, $school, $refund->id, 'reject', null);
            $this->fail('A rejection must include a review reason.');
        } catch (ValidationException $exception) {
            $this->assertSame('A reason is required when rejecting a refund request.', $exception->errors()['review_note'][0]);
        }

        $rejected = $reviewService->handle($reviewer, $school, $refund->id, 'reject', 'Supporting details were not provided.');

        $this->assertSame('rejected', $rejected->status);
        $this->assertSame(10000, $receipt->fresh()->availableMinor());

        $approvedRefund = $requestService->handle($requester, $school, $receipt->id, [
            'refund_key' => 'bank-refund-approved',
            'amount_minor' => 3000,
            'refund_method' => 'bank',
            'reason' => 'Return duplicate payment',
        ]);
        $reviewService->handle($reviewer, $school, $approvedRefund->id, 'approve', null);

        try {
            app(CompleteSchoolReceiptRefund::class)->handle($reviewer, $school, $approvedRefund->id, 'bank-payout-missing-reference', null);
            $this->fail('A bank payout requires its reference.');
        } catch (ValidationException $exception) {
            $this->assertSame('A bank or M-Pesa payout reference is required.', $exception->errors()['payout_reference'][0]);
        }

        app(CompleteSchoolReceiptRefund::class)->handle($reviewer, $school, $approvedRefund->id, 'bank-payout-unique-1', 'BANK-REFUND-101');
        $duplicateRefund = $requestService->handle($requester, $school, $receipt->id, [
            'refund_key' => 'bank-refund-duplicate-reference',
            'amount_minor' => 1000,
            'refund_method' => 'bank',
            'reason' => 'Second payment correction',
        ]);
        $reviewService->handle($reviewer, $school, $duplicateRefund->id, 'approve', null);

        try {
            app(CompleteSchoolReceiptRefund::class)->handle($reviewer, $school, $duplicateRefund->id, 'bank-payout-unique-2', 'BANK-REFUND-101');
            $this->fail('A payout reference cannot be recorded for two refunds.');
        } catch (ValidationException $exception) {
            $this->assertSame('This payout reference is already recorded for another refund.', $exception->errors()['payout_reference'][0]);
        }

        $this->assertDatabaseHas('school_refunds', ['id' => $duplicateRefund->id, 'status' => 'approved']);
        $this->assertSame(6000, $receipt->fresh()->availableMinor());
    }

    public function test_school_admins_can_use_the_fee_workspace_refund_actions(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $receipt = $this->recordReceipt($school, $requester, 8000);
        $this->actingAs($requester);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('requestSchoolReceiptRefund', [
                'school_receipt_id' => $receipt->id,
                'amount_minor' => 2500,
                'refund_method' => 'mpesa',
                'reason' => 'Duplicate payment',
                'refund_key' => 'workspace-refund-001',
            ])
            ->assertHasNoErrors();

        $refund = SchoolRefund::query()->where('refund_key', 'workspace-refund-001')->firstOrFail();
        $this->actingAs($reviewer);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('reviewSchoolReceiptRefund', [
                'school_refund_id' => $refund->id,
                'decision' => 'approve',
                'review_note' => null,
            ])
            ->assertHasNoErrors()
            ->callAction('completeSchoolReceiptRefund', [
                'school_refund_id' => $refund->id,
                'payout_reference' => 'MPESA-REFUND-001',
                'completion_key' => 'workspace-refund-paid-001',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_refunds', [
            'id' => $refund->id,
            'status' => 'paid',
            'completed_by_user_id' => $reviewer->id,
            'payout_reference' => 'MPESA-REFUND-001',
        ]);
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

    private function recordReceipt(School $school, User $actor, int $amountMinor): SchoolReceipt
    {
        return app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'refund-receipt-'.str()->uuid(),
            'currency' => 'KES',
            'amount_minor' => $amountMinor,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
    }
}
