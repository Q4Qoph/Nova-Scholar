<?php

namespace Tests\Feature;

use App\Filament\School\Pages\FeeOperations;
use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeReceiptAllocation;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\PostFeeChargeBatch;
use App\Services\Schools\RecordSchoolReceipt;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFeeOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_tenant_scoped_fee_operations(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'fee-operations-school', 'name' => 'Fee Operations School']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $schedule = FeeSchedule::factory()->for($school)->create(['name' => 'Term Tuition']);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => 250000,
            'posted_at' => now(),
        ]);
        $learner = Enrolment::factory()->for($school)->create();
        FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($learner)->create();
        app(RecordSchoolReceipt::class)->handle($admin, $school, [
            'source' => 'bank',
            'source_reference' => 'FILAMENT-RECEIPT-001',
            'submission_key' => 'd8afbdd8-2ad5-4b26-a21d-1c5a78a9862a',
            'currency' => 'KES',
            'amount_minor' => 100000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);

        $this->actingAs($admin)
            ->get('/school/fee-operations-school/fee-operations')
            ->assertOk()
            ->assertSee('Fee Operations School')
            ->assertSee('Term Tuition')
            ->assertSee('KES 2,500.00')
            ->assertSee($batch->batch_key)
            ->assertSee('Recent receipts')
            ->assertSee('FILAMENT-RECEIPT-001')
            ->assertSee('KES 1,000.00 available')
            ->assertDontSee(route('schools.fees.index', $school), false)
            ->assertSee($learner->learnerProfile->first_name);
    }

    public function test_teacher_cannot_view_fee_operations(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-fee-operations-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher)
            ->get('/school/blocked-fee-operations-school/fee-operations')
            ->assertForbidden();
    }

    public function test_bursar_cannot_view_school_admin_fee_operations(): void
    {
        $bursar = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-bursar-fee-operations-school']);
        $membership = $school->memberships()->create([
            'user_id' => $bursar->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Bursar]);

        $this->actingAs($bursar)
            ->get('/school/blocked-bursar-fee-operations-school/fee-operations')
            ->assertForbidden();
    }

    public function test_fee_data_getters_recheck_membership_after_the_page_mounts(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'revoked-fee-operations-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $this->actingAs($admin);
        Filament::setTenant($school);

        $component = Livewire::test(FeeOperations::class);
        $membership->roles()->delete();

        $component->call('getRecentReceipts')->assertForbidden();
    }

    public function test_school_admin_can_create_preview_post_record_and_allocate_from_filament_actions(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'fee-actions-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $learner = Enrolment::factory()->for($school)->create(['admission_number' => 'FILAMENT-FEE-001']);

        $this->actingAs($admin);
        Filament::setTenant($school);
        $component = Livewire::test(FeeOperations::class);

        $component->callAction('createFeeSchedule', [
            'name' => 'Panel tuition',
            'currency' => 'KES',
            'amount_minor' => 25000,
            'term_id' => null,
            'class_group_id' => null,
            'starts_on' => today()->toDateString(),
            'ends_on' => null,
        ])->assertHasNoErrors();

        $schedule = FeeSchedule::query()->where('school_id', $school->id)->sole();

        $component->callAction('previewFeeBatch', [
            'fee_schedule_id' => $schedule->id,
            'batch_key' => 'filament-panel-batch',
        ])->assertHasNoErrors();

        $batch = FeeChargeBatch::query()->where('school_id', $school->id)->sole();
        $this->assertSame(1, $batch->eligible_count);
        $this->assertNotNull($batch->preview_hash);

        $component->callAction('postFeeBatch', ['fee_charge_batch_id' => $batch->id])->assertHasNoErrors();

        $charge = $school->feeCharges()->sole();
        $component->callAction('recordSchoolReceipt', [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'a7bde01a-6bc8-4d30-bc16-846210bc132d',
            'currency' => 'KES',
            'amount_minor' => 25000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ])->assertHasNoErrors();

        $receipt = $school->receipts()->sole();
        $component->callAction('allocateSchoolReceipt', [
            'school_receipt_id' => $receipt->id,
            'fee_charge_id' => $charge->id,
            'amount_minor' => 15000,
            'allocation_key' => '85bc2b2a-97f5-4458-b19d-1aec40801e8a',
        ])->assertHasNoErrors();

        $this->assertDatabaseHas('fee_receipt_allocations', [
            'school_receipt_id' => $receipt->id,
            'fee_charge_id' => $charge->id,
            'allocated_by_user_id' => $admin->id,
            'amount_minor' => 15000,
        ]);
        $this->assertSame($learner->id, $charge->enrolment_id);
        $this->assertDatabaseCount('audit_events', 4);
        $this->assertSame(1, FeeReceiptAllocation::query()->where('school_id', $school->id)->count());
    }

    public function test_allocation_action_rejects_receipt_and_charge_ids_from_another_school(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'fee-actions-tenant-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $otherAdmin = User::factory()->create();
        $otherSchool = School::factory()->create();
        $otherMembership = $otherSchool->memberships()->create([
            'user_id' => $otherAdmin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $otherMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $otherLearner = Enrolment::factory()->for($otherSchool)->create();
        $otherSchedule = FeeSchedule::factory()->for($otherSchool)->create(['amount_minor' => 20000]);
        $otherBatch = app(PostFeeChargeBatch::class)->preview($otherAdmin, $otherSchool, $otherSchedule, 'foreign-panel-batch');
        $otherPostedBatch = app(PostFeeChargeBatch::class)->handle($otherAdmin, $otherSchool, $otherSchedule, $otherBatch->batch_key);
        $otherReceipt = app(RecordSchoolReceipt::class)->handle($otherAdmin, $otherSchool, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => '2439eeb2-9176-4db8-9f92-4e1baef20e0c',
            'currency' => 'KES',
            'amount_minor' => 20000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(FeeOperations::class)
            ->callAction('allocateSchoolReceipt', [
                'school_receipt_id' => $otherReceipt->id,
                'fee_charge_id' => $otherPostedBatch->charges->sole()->id,
                'amount_minor' => 10000,
                'allocation_key' => '813a6fc2-af17-46fc-9729-5c9848096cdd',
            ])
            ->assertHasErrors();

        $this->assertDatabaseCount('fee_receipt_allocations', 0);
        $this->assertSame($otherLearner->id, $otherPostedBatch->charges->sole()->enrolment_id);
    }

    public function test_fee_page_is_denied_after_school_admin_membership_is_revoked(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'revoked-fee-action-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($admin);
        Filament::setTenant($school);
        $this->get('/school/revoked-fee-action-school/fee-operations')->assertOk();
        $membership->roles()->delete();

        $this->get('/school/revoked-fee-action-school/fee-operations')->assertForbidden();

        $this->assertDatabaseCount('school_receipts', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
