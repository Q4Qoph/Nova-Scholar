<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\FeeSchedule;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\PostFeeChargeBatch;
use App\Services\Schools\RecordSchoolReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchoolFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_preview_post_and_retry_an_idempotent_class_charge_batch(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $classGroup = $this->classGroup($school);
        $firstLearner = $this->placeLearner($school, $classGroup, 'F-001');
        $secondLearner = $this->placeLearner($school, $classGroup, 'F-002');
        Enrolment::factory()->for($school)->create(['admission_number' => 'F-003']);

        $this->actingAs($admin)->post(route('schools.fee-schedules.store', $school), [
            'name' => 'Grade 5 tuition',
            'currency' => 'KES',
            'amount_minor' => 125000,
            'class_group_id' => $classGroup->id,
        ])->assertRedirectToRoute('schools.fees.index', $school);
        $schedule = FeeSchedule::query()->firstOrFail();
        $batchPayload = ['fee_schedule_id' => $schedule->id, 'batch_key' => 'grade-5-term-1'];

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.preview', $school), $batchPayload)
            ->assertOk()
            ->assertSee('2 active enrolments')
            ->assertSee('KES 2,500.00');

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $batchPayload)->assertRedirect();
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $batchPayload)->assertRedirect();

        $this->assertDatabaseCount('fee_charges', 2);
        $this->assertDatabaseHas('fee_charges', ['enrolment_id' => $firstLearner->id, 'amount_minor' => 125000, 'status' => 'posted']);
        $this->assertDatabaseHas('fee_charges', ['enrolment_id' => $secondLearner->id, 'amount_minor' => 125000, 'status' => 'posted']);
        $this->assertDatabaseCount('audit_events', 2);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'fee_schedule.created', 'actor_user_id' => $admin->id]);
    }

    public function test_posted_charge_keeps_its_snapshot_when_schedule_changes(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 90000]);
        Enrolment::factory()->for($school)->create();
        $payload = ['fee_schedule_id' => $schedule->id, 'batch_key' => 'snapshot-1'];

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.preview', $school), $payload)->assertOk();
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $payload)->assertRedirect();
        $schedule->update(['amount_minor' => 110000, 'status' => 'inactive']);
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $payload)->assertRedirect();

        $this->assertDatabaseHas('fee_charges', ['fee_schedule_id' => $schedule->id, 'amount_minor' => 90000]);
        $this->assertDatabaseCount('fee_charges', 1);
    }

    public function test_fee_batch_cannot_be_posted_without_a_preview(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $schedule = FeeSchedule::factory()->for($school)->create();
        Enrolment::factory()->for($school)->create();

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.post', $school), [
                'fee_schedule_id' => $schedule->id,
                'batch_key' => 'preview-required',
            ])
            ->assertSessionHasErrors('batch_key');

        $this->assertDatabaseCount('fee_charge_batches', 0);
        $this->assertDatabaseCount('fee_charges', 0);
    }

    public function test_changed_eligible_learners_require_a_refreshed_preview(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $schedule = FeeSchedule::factory()->for($school)->create();
        Enrolment::factory()->for($school)->create(['admission_number' => 'PREVIEW-001']);
        $payload = ['fee_schedule_id' => $schedule->id, 'batch_key' => 'preview-refresh'];

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.preview', $school), $payload)->assertOk();
        $schedule->update(['amount_minor' => 300000]);

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.post', $school), $payload)
            ->assertSessionHasErrors('batch_key');
        $this->assertDatabaseCount('fee_charges', 0);

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.preview', $school), $payload)->assertOk();
        Enrolment::factory()->for($school)->create(['admission_number' => 'PREVIEW-002']);

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.post', $school), $payload)
            ->assertSessionHasErrors('batch_key');

        $this->assertDatabaseCount('fee_charges', 0);

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.preview', $school), $payload)->assertOk();
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $payload)->assertRedirect();

        $this->assertDatabaseCount('fee_charges', 2);
        $this->assertDatabaseHas('fee_charges', ['fee_schedule_id' => $schedule->id, 'amount_minor' => 300000]);
    }

    public function test_batch_key_cannot_be_reused_for_another_schedule_after_posting(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $firstSchedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000]);
        $secondSchedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 20000]);
        Enrolment::factory()->for($school)->create();
        $firstPayload = ['fee_schedule_id' => $firstSchedule->id, 'batch_key' => 'single-use-key'];

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.preview', $school), $firstPayload)->assertOk();
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $firstPayload)->assertRedirect();

        $secondPayload = ['fee_schedule_id' => $secondSchedule->id, 'batch_key' => 'single-use-key'];
        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.preview', $school), $secondPayload)
            ->assertSessionHasErrors('batch_key');
        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.post', $school), $secondPayload)
            ->assertSessionHasErrors('batch_key');

        $this->assertDatabaseCount('fee_charges', 1);
        $this->assertDatabaseHas('fee_charges', ['fee_schedule_id' => $firstSchedule->id, 'amount_minor' => 10000]);
        $this->assertDatabaseMissing('fee_charges', ['fee_schedule_id' => $secondSchedule->id]);
    }

    public function test_fee_schedule_must_be_current_and_term_must_be_open_before_preview(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $expiredSchedule = FeeSchedule::factory()->for($school)->create(['ends_on' => today()->subDay()]);
        $academicYear = AcademicYear::factory()->for($school)->create();
        $closedTerm = Term::factory()->for($school)->for($academicYear)->create(['status' => 'closed']);
        $closedTermSchedule = FeeSchedule::factory()->for($school)->create(['term_id' => $closedTerm->id]);

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.preview', $school), [
                'fee_schedule_id' => $expiredSchedule->id,
                'batch_key' => 'expired-schedule',
            ])
            ->assertSessionHasErrors('fee_schedule_id');
        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.preview', $school), [
                'fee_schedule_id' => $closedTermSchedule->id,
                'batch_key' => 'closed-term',
            ])
            ->assertSessionHasErrors('fee_schedule_id');

        $this->assertDatabaseCount('fee_charge_batches', 0);
    }

    public function test_fee_pages_display_integer_minor_units_as_currency_amounts(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $kesSchedule = FeeSchedule::factory()->for($school)->create(['currency' => 'KES', 'amount_minor' => 125000]);
        $jpySchedule = FeeSchedule::factory()->for($school)->create(['currency' => 'JPY', 'amount_minor' => 125000]);
        $kwdSchedule = FeeSchedule::factory()->for($school)->create(['currency' => 'KWD', 'amount_minor' => 125000]);

        $this->actingAs($admin)
            ->get(route('schools.fees.index', $school))
            ->assertOk()
            ->assertSee('KES 1,250.00')
            ->assertSee('JPY 125,000')
            ->assertSee('KWD 125.000')
            ->assertDontSee('KES 125,000');

        $this->assertModelExists($kesSchedule);
        $this->assertModelExists($jpySchedule);
        $this->assertModelExists($kwdSchedule);
    }

    public function test_fee_schedule_form_offers_only_open_terms(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $academicYear = AcademicYear::factory()->for($school)->create();
        Term::factory()->for($school)->for($academicYear)->create(['name' => 'Open term', 'status' => 'open']);
        Term::factory()->for($school)->for($academicYear)->create(['name' => 'Closed term', 'status' => 'closed']);

        $this->actingAs($admin)
            ->get(route('schools.fees.index', $school))
            ->assertOk()
            ->assertSee('Open term')
            ->assertDontSee('Closed term');
    }

    public function test_only_school_admins_can_manage_fee_schedules_and_batches(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);

        $this->actingAs($teacher)->get(route('schools.fees.index', $school))->assertForbidden();
        $this->actingAs($teacher)->post(route('schools.fee-schedules.store', $school), [
            'name' => 'Blocked',
            'currency' => 'KES',
            'amount_minor' => 100,
        ])->assertForbidden();

        $this->actingAs($teacher)->post(route('schools.receipts.store', $school), [
            'source' => 'cash',
            'submission_key' => '9ce26b8a-8009-49a9-b1da-2653435fbde0',
            'currency' => 'KES',
            'amount_minor' => 100,
            'received_on' => today()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($teacher)->post(route('schools.receipts.allocations.store', $school), [])->assertForbidden();
    }

    public function test_school_admin_can_record_a_confirmed_receipt_and_allocate_it_across_multiple_learner_charges(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        Enrolment::factory()->for($school)->count(2)->sequence(
            ['admission_number' => 'RCPT-001'],
            ['admission_number' => 'RCPT-002'],
        )->create();
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000, 'currency' => 'KES']);
        $batch = app(PostFeeChargeBatch::class)->preview($admin, $school, $schedule, 'receipts-batch');
        app(PostFeeChargeBatch::class)->handle($admin, $school, $schedule, $batch->batch_key);
        $charges = $school->feeCharges()->orderBy('id')->get();
        $submissionKey = 'a97c1270-5a9c-45fa-8430-72cc846646b1';

        $this->actingAs($admin)->post(route('schools.receipts.store', $school), [
            'source' => 'bank',
            'source_reference' => ' bank-123 ',
            'submission_key' => $submissionKey,
            'currency' => 'kes',
            'amount_minor' => 20000,
            'received_on' => today()->toDateString(),
            'verification_note' => 'Confirmed against bank statement.',
        ])->assertRedirectToRoute('schools.fees.index', $school);
        $receipt = SchoolReceipt::query()->firstOrFail();

        $this->assertSame('BANK-123', $receipt->source_reference);
        $this->assertSame($admin->id, $receipt->verified_by_user_id);
        $this->assertNotNull($receipt->verified_at);
        $this->assertDatabaseCount('audit_events', 2);

        foreach ([[$charges[0], 9000, 'b2417dd5-c4ac-4f39-9994-0f8d3a34f6be'], [$charges[1], 6000, 'e3b14c1e-0659-4f33-a4de-e36e17552e90']] as [$charge, $amountMinor, $allocationKey]) {
            $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), [
                'school_receipt_id' => $receipt->id,
                'fee_charge_id' => $charge->id,
                'amount_minor' => $amountMinor,
                'allocation_key' => $allocationKey,
            ])->assertRedirectToRoute('schools.fees.index', $school);
        }

        $this->assertDatabaseCount('fee_receipt_allocations', 2);
        $this->assertDatabaseHas('fee_receipt_allocations', ['fee_charge_id' => $charges[0]->id, 'amount_minor' => 9000]);
        $this->assertDatabaseHas('fee_receipt_allocations', ['fee_charge_id' => $charges[1]->id, 'amount_minor' => 6000]);
        $this->assertDatabaseCount('audit_events', 4);

        $this->actingAs($admin)->get(route('schools.fees.index', $school))
            ->assertOk()
            ->assertSee('KES 200.00')
            ->assertSee('KES 150.00 allocated')
            ->assertSee('KES 50.00 available')
            ->assertSee('KES 10.00 due')
            ->assertSee('name="school_receipt_id"', false)
            ->assertSee('name="fee_charge_id"', false);
    }

    public function test_receipt_submission_retry_is_idempotent_and_source_reference_is_unique(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $payload = [
            'source' => 'mpesa',
            'source_reference' => 'QWE123RTY',
            'submission_key' => '5a8f55ae-7fb8-4f95-b17f-b0f294465c61',
            'currency' => 'KES',
            'amount_minor' => 50000,
            'received_on' => today()->toDateString(),
        ];

        $this->actingAs($admin)->post(route('schools.receipts.store', $school), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('schools.receipts.store', $school), $payload)->assertRedirect();

        $this->assertDatabaseCount('school_receipts', 1);
        $this->assertDatabaseCount('audit_events', 1);

        $payload['submission_key'] = '228508cb-80e8-49b0-9028-59629759b0b4';
        $this->actingAs($admin)->post(route('schools.receipts.store', $school), $payload)
            ->assertSessionHasErrors(['source_reference' => 'A receipt with this source reference has already been recorded.']);
        $this->assertDatabaseCount('school_receipts', 1);
    }

    public function test_allocation_cannot_overdraw_receipt_or_charge_and_replay_does_not_duplicate(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        Enrolment::factory()->for($school)->create(['admission_number' => 'RCPT-LIMIT']);
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000, 'currency' => 'KES']);
        $batch = app(PostFeeChargeBatch::class)->preview($admin, $school, $schedule, 'receipt-limit');
        $postedBatch = app(PostFeeChargeBatch::class)->handle($admin, $school, $schedule, $batch->batch_key);
        $charge = $postedBatch->charges->sole();
        $this->actingAs($admin)->post(route('schools.receipts.store', $school), [
            'source' => 'cash',
            'submission_key' => '8a336298-8ec0-446f-980d-d4428aa3a121',
            'currency' => 'KES',
            'amount_minor' => 10000,
            'received_on' => today()->toDateString(),
        ])->assertRedirect();
        $receipt = SchoolReceipt::query()->firstOrFail();
        $allocationPayload = [
            'school_receipt_id' => $receipt->id,
            'fee_charge_id' => $charge->id,
            'amount_minor' => 10000,
            'allocation_key' => '587c0152-a387-4e4f-a588-40f2f41cb8d1',
        ];

        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), $allocationPayload)->assertRedirect();
        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), $allocationPayload)->assertRedirect();

        $this->assertDatabaseCount('fee_receipt_allocations', 1);

        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), [
            ...$allocationPayload,
            'allocation_key' => '38f935f3-1e49-4631-ae10-42f4b4fc47ef',
            'amount_minor' => 1,
        ])->assertSessionHasErrors('amount_minor');

        $this->assertDatabaseCount('fee_receipt_allocations', 1);
        $this->assertDatabaseCount('audit_events', 3);
    }

    public function test_allocation_cannot_exceed_a_charge_already_paid_by_another_receipt(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        Enrolment::factory()->for($school)->create(['admission_number' => 'RCPT-CHARGE-LIMIT']);
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000]);
        $batch = app(PostFeeChargeBatch::class)->preview($admin, $school, $schedule, 'receipt-charge-limit');
        $postedBatch = app(PostFeeChargeBatch::class)->handle($admin, $school, $schedule, $batch->batch_key);
        $charge = $postedBatch->charges->sole();
        $receiptPayload = [
            'source' => 'cash',
            'currency' => 'KES',
            'amount_minor' => 10000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ];

        foreach (['d875511a-02e4-45c7-b34d-8df935aaef05', 'e678f4ef-6a69-419b-830b-b2a550b1c5d4'] as $submissionKey) {
            app(RecordSchoolReceipt::class)->handle($admin, $school, [...$receiptPayload, 'source_reference' => null, 'submission_key' => $submissionKey]);
        }

        $receipts = $school->receipts()->orderBy('id')->get();
        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), [
            'school_receipt_id' => $receipts[0]->id,
            'fee_charge_id' => $charge->id,
            'amount_minor' => 10000,
            'allocation_key' => '28062254-abd0-4c76-9cbb-4ff6605a7fa1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), [
            'school_receipt_id' => $receipts[1]->id,
            'fee_charge_id' => $charge->id,
            'amount_minor' => 1,
            'allocation_key' => 'f756c7f7-d135-4800-9e29-9664953f1f32',
        ])->assertSessionHasErrors(['amount_minor' => 'The allocation exceeds the outstanding charge balance.']);

        $this->assertDatabaseCount('fee_receipt_allocations', 1);
    }

    public function test_receipt_and_charge_ids_from_another_school_are_not_accepted(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        [$otherAdmin, $otherSchool] = $this->schoolContext(SchoolRole::SchoolAdmin);
        Enrolment::factory()->for($otherSchool)->create(['admission_number' => 'OTHER-RCPT']);
        $otherSchedule = FeeSchedule::factory()->for($otherSchool)->create(['amount_minor' => 5000]);
        $otherBatch = app(PostFeeChargeBatch::class)->preview($otherAdmin, $otherSchool, $otherSchedule, 'other-school-receipt');
        $postedOtherBatch = app(PostFeeChargeBatch::class)->handle($otherAdmin, $otherSchool, $otherSchedule, $otherBatch->batch_key);
        $foreignCharge = $postedOtherBatch->charges->sole();
        app(RecordSchoolReceipt::class)->handle($otherAdmin, $otherSchool, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => '35292795-491d-4917-8ab0-6de7b8c1bd32',
            'currency' => 'KES',
            'amount_minor' => 5000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
        $foreignReceipt = SchoolReceipt::query()->firstOrFail();

        $this->actingAs($admin)->post(route('schools.receipts.allocations.store', $school), [
            'school_receipt_id' => $foreignReceipt->id,
            'fee_charge_id' => $foreignCharge->id,
            'amount_minor' => 100,
            'allocation_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors(['school_receipt_id', 'fee_charge_id']);

        $this->assertDatabaseCount('fee_receipt_allocations', 0);
    }

    /** @return array{0: User, 1: School} */
    private function schoolContext(SchoolRole $role): array
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => $role]);

        return [$user, $school];
    }

    private function classGroup(School $school): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);

        return ClassGroup::factory()->for($school)->for($academicYear)->create();
    }

    private function placeLearner(School $school, ClassGroup $classGroup, string $admissionNumber): Enrolment
    {
        $enrolment = Enrolment::factory()->for($school)->create(['admission_number' => $admissionNumber]);
        LearnerClassMembership::factory()->create(['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'class_group_id' => $classGroup->id, 'starts_on' => '2026-01-01']);

        return $enrolment;
    }
}
