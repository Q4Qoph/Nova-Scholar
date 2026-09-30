<?php

namespace Tests\Feature;

use App\Filament\School\Pages\FeeOperations;
use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeReceiptAllocation;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\ReverseSchoolReceiptAllocation;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolFeeAllocationReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_reversal_restores_receipt_and_charge_balances_and_preserves_original_records(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$charge, $receipt, $allocation] = $this->postedChargeWithAllocation($school, $admin);

        $reversal = app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $allocation->id, 'Applied to the wrong learner.', 'allocation-reversal-001');
        $replayedReversal = app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $allocation->id, 'Applied to the wrong learner.', 'allocation-reversal-001');

        $this->assertSame($reversal->id, $replayedReversal->id);
        $this->assertSame(4000, $reversal->amount_minor);
        $this->assertSame(10000, $charge->fresh()->outstandingMinor());
        $this->assertSame(4000, $receipt->fresh()->availableMinor());
        $this->assertSame(4000, $allocation->fresh()->amount_minor);
        $this->assertSame(10000, $charge->fresh()->amount_minor);
        $this->assertSame(4000, $receipt->fresh()->amount_minor);
        $this->assertDatabaseCount('fee_receipt_allocation_reversals', 1);
        $this->assertDatabaseCount('audit_events', 3);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'fee_receipt_allocation.reversed',
            'auditable_id' => $reversal->id,
        ]);
    }

    public function test_reversal_key_cannot_be_reused_with_changed_details_and_an_allocation_cannot_be_reversed_twice(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$charge, $receipt, $allocation] = $this->postedChargeWithAllocation($school, $admin);
        $service = app(ReverseSchoolReceiptAllocation::class);
        $service->handle($admin, $school, $allocation->id, 'Original reason.', 'allocation-reversal-replay');

        try {
            $service->handle($admin, $school, $allocation->id, 'Changed reason.', 'allocation-reversal-replay');
            $this->fail('A reversal key cannot be reused with changed details.');
        } catch (ValidationException $exception) {
            $this->assertSame('This reversal key was already used for different details.', $exception->errors()['reversal_key'][0]);
        }

        try {
            $service->handle($admin, $school, $allocation->id, 'Second reversal attempt.', 'allocation-reversal-second');
            $this->fail('An allocation can only be reversed once.');
        } catch (ValidationException $exception) {
            $this->assertSame('This allocation has already been reversed.', $exception->errors()['fee_receipt_allocation_id'][0]);
        }

        $this->assertSame(10000, $charge->fresh()->outstandingMinor());
        $this->assertSame(4000, $receipt->fresh()->availableMinor());
        $this->assertDatabaseCount('fee_receipt_allocation_reversals', 1);
        $this->assertDatabaseCount('audit_events', 3);
    }

    public function test_reversal_requires_a_reason_and_key(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [, , $allocation] = $this->postedChargeWithAllocation($school, $admin);

        try {
            app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $allocation->id, '  ', 'valid-key');
            $this->fail('A non-empty reason is required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        try {
            app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $allocation->id, 'Valid reason.', '');
            $this->fail('A non-empty reversal key is required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reversal_key', $exception->errors());
        }

        $this->assertDatabaseCount('fee_receipt_allocation_reversals', 0);
    }

    public function test_only_school_admins_can_reverse_and_foreign_allocations_are_not_found(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$otherSchool, $otherAdmin] = $this->schoolWithAdmins();
        $teacher = $this->addSchoolRole($school, SchoolRole::Teacher);
        [, , $foreignAllocation] = $this->postedChargeWithAllocation($otherSchool, $otherAdmin);

        try {
            app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $foreignAllocation->id, 'Foreign allocation.', 'foreign-allocation-reversal');
            $this->fail('An allocation from another school must not be found.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('fee_receipt_allocation_reversals', 0);
        }

        [, , $allocation] = $this->postedChargeWithAllocation($school, $admin);

        try {
            app(ReverseSchoolReceiptAllocation::class)->handle($teacher, $school, $allocation->id, 'Teacher attempt.', 'teacher-allocation-reversal');
            $this->fail('Teachers cannot reverse school fee allocations.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('fee_receipt_allocation_reversals', 0);
        }
    }

    public function test_reversed_receipt_funds_can_be_reallocated(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$charge, $receipt, $allocation] = $this->postedChargeWithAllocation($school, $admin);
        app(ReverseSchoolReceiptAllocation::class)->handle($admin, $school, $allocation->id, 'Move payment to another charge.', 'allocation-reversal-reallocate');
        $nextCharge = $this->postedCharge($school, $admin, 5000);

        $newAllocation = app(AllocateSchoolReceipt::class)->handle($admin, $school, $receipt->id, $nextCharge->id, 4000, 'reallocated-released-funds');

        $this->assertSame(0, $receipt->fresh()->availableMinor());
        $this->assertSame(10000, $charge->fresh()->outstandingMinor());
        $this->assertSame(1000, $nextCharge->fresh()->outstandingMinor());
        $this->assertInstanceOf(FeeReceiptAllocation::class, $newAllocation);
        $this->assertDatabaseCount('fee_receipt_allocations', 2);
    }

    public function test_school_admin_can_reverse_an_allocation_in_the_fee_workspace(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        [$charge, $receipt, $allocation] = $this->postedChargeWithAllocation($school, $admin);
        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('reverseSchoolReceiptAllocation', [
                'fee_receipt_allocation_id' => $allocation->id,
                'reason' => 'Wrong learner selection.',
                'reversal_key' => 'filament-allocation-reversal-001',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fee_receipt_allocation_reversals', [
            'fee_receipt_allocation_id' => $allocation->id,
            'reversed_by_user_id' => $admin->id,
        ]);

        $this->get('/school/'.$school->slug.'/fee-operations')
            ->assertOk()
            ->assertSee('KES 100.00 outstanding')
            ->assertSee('KES 40.00 available')
            ->assertSee('Allocation reversed; no cash refund issued.')
            ->assertSee('Wrong learner selection.');
    }

    /** @return array{0: School, 1: User} */
    private function schoolWithAdmins(): array
    {
        $school = School::factory()->create();

        return [$school, $this->addSchoolRole($school, SchoolRole::SchoolAdmin)];
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

    /** @return array{0: FeeCharge, 1: SchoolReceipt, 2: FeeReceiptAllocation} */
    private function postedChargeWithAllocation(School $school, User $creator): array
    {
        $charge = $this->postedCharge($school, $creator, 10000);
        $receipt = app(RecordSchoolReceipt::class)->handle($creator, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'reversal-receipt-'.str()->uuid(),
            'currency' => 'KES',
            'amount_minor' => 4000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
        $allocation = app(AllocateSchoolReceipt::class)->handle($creator, $school, $receipt->id, $charge->id, 4000, 'reversal-allocation-'.str()->uuid());

        return [$charge, $receipt, $allocation];
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
