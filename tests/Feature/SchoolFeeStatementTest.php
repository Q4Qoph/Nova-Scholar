<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\GuardianLink;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\BuildSchoolFeeStatement;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolFeeCredit;
use App\Services\Schools\ReverseSchoolReceiptAllocation;
use App\Services\Schools\ReviewSchoolFeeCredit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFeeStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_uses_local_dates_and_reconciles_charges_credits_allocations_and_reversals(): void
    {
        [$school, $requester, $reviewer] = $this->schoolWithAdmins(2);
        $enrolment = $this->enrolment($school, 'ST-001');
        $charge = $this->postedCharge($school, $enrolment, $requester, 10000, '2026-09-10');
        $receipt = $this->recordReceipt($school, $requester, 6000, '2026-09-12');

        $this->travelTo('2026-09-12 22:30:00 UTC');
        $allocation = app(AllocateSchoolReceipt::class)->handle($requester, $school, $receipt->id, $charge->id, 4000, 'statement-allocation-001');
        $this->travelTo('2026-09-13 10:00:00 UTC');
        $credit = app(RequestSchoolFeeCredit::class)->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'statement-credit-001',
            'amount_minor' => 1000,
            'reason' => 'Approved reduction',
        ]);
        app(ReviewSchoolFeeCredit::class)->handle($reviewer, $school, $credit->id, 'approve', null);
        $this->travelTo('2026-09-13 22:30:00 UTC');
        app(ReverseSchoolReceiptAllocation::class)->handle($reviewer, $school, $allocation->id, 'Correct the learner allocation.', 'statement-reversal-001');
        $this->travelTo('2026-09-15 09:00:00 UTC');
        app(AllocateSchoolReceipt::class)->handle($requester, $school, $receipt->id, $charge->id, 3000, 'statement-allocation-002');

        $statement = app(BuildSchoolFeeStatement::class)->handle($school, $enrolment, '2026-09-13', '2026-09-14');
        $group = $statement['groups']->get('KES');

        $this->assertSame('Africa/Nairobi', $statement['timezone']);
        $this->assertSame(10000, $group['opening_minor']);
        $this->assertSame(-1000, $group['activity_minor']);
        $this->assertSame(9000, $group['closing_minor']);
        $this->assertSame(['credit', 'allocation', 'reversal'], array_column($group['rows'], 'type'));
        $this->assertSame(['2026-09-13', '2026-09-13', '2026-09-14'], array_column($group['rows'], 'date'));
        $this->assertSame([9000, 5000, 9000], array_column($group['rows'], 'running_balance_minor'));
        $this->assertSame($statement['reference'], app(BuildSchoolFeeStatement::class)->handle($school, $enrolment, '2026-09-13', '2026-09-14')['reference']);

        $this->postedCharge($school, $enrolment, $requester, 500, '2026-09-14');
        $changedStatement = app(BuildSchoolFeeStatement::class)->handle($school, $enrolment, '2026-09-13', '2026-09-14');

        $this->assertNotSame($statement['reference'], $changedStatement['reference']);
    }

    public function test_school_statement_and_csv_are_limited_to_school_admins_and_scope_the_enrolment(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $teacher = $this->addSchoolRole($school, SchoolRole::Teacher);
        [$otherSchool, $otherAdmin] = $this->schoolWithAdmins();
        $enrolment = $this->enrolment($school, 'ST-002');
        $foreignEnrolment = $this->enrolment($otherSchool, 'ST-003');
        $charge = $this->postedCharge($school, $enrolment, $admin, 2500, '2026-09-10', '=SUM(1,2)');

        $this->actingAs($admin)
            ->get(route('schools.fees.statements.show', [$school, $enrolment]))
            ->assertOk()
            ->assertSee('=SUM(1,2)')
            ->assertSee('Closing receivable');

        $csvResponse = $this->actingAs($admin)
            ->get(route('schools.fees.statements.export', [$school, $enrolment]));

        $csvResponse->assertDownload();
        $this->assertStringContainsString("'=SUM(1,2)", $csvResponse->streamedContent());

        $this->actingAs($teacher)
            ->get(route('schools.fees.statements.show', [$school, $enrolment]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('schools.fees.statements.show', [$school, $foreignEnrolment]))
            ->assertNotFound();

        $this->assertModelExists($charge);
        $this->assertSame($otherAdmin->id, $foreignEnrolment->school->memberships()->where('user_id', $otherAdmin->id)->value('user_id'));
    }

    public function test_guardian_statement_rechecks_the_verified_link_and_never_exposes_a_sibling_ledger(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $guardian = User::factory()->create();
        $learner = $this->enrolment($school, 'ST-004');
        $sibling = $this->enrolment($school, 'ST-005');
        $this->postedCharge($school, $learner, $admin, 2000, '2026-09-10', 'Selected learner tuition');
        $this->postedCharge($school, $sibling, $admin, 9000, '2026-09-10', 'Sibling private charge');
        $link = GuardianLink::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'verified_by_user_id' => $admin->id,
            'status' => 'active',
            'verified_at' => now(),
            'revoked_at' => null,
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.statements.show', $learner))
            ->assertOk()
            ->assertSee('Selected learner tuition')
            ->assertDontSee('Sibling private charge');

        $this->actingAs($guardian)
            ->get(route('guardian.learners.statements.export', $learner))
            ->assertDownload();

        $this->actingAs($guardian)
            ->get(route('guardian.learners.statements.show', $sibling))
            ->assertNotFound();

        $link->update(['status' => 'revoked', 'revoked_at' => now()]);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.statements.show', $learner))
            ->assertNotFound();
    }

    public function test_statement_period_validation_rejects_an_end_date_before_the_start_date(): void
    {
        [$school, $admin] = $this->schoolWithAdmins();
        $enrolment = $this->enrolment($school, 'ST-006');

        $this->actingAs($admin)
            ->get(route('schools.fees.statements.show', [
                'school' => $school,
                'enrolment' => $enrolment,
                'from' => '2026-09-20',
                'to' => '2026-09-10',
            ]))
            ->assertSessionHasErrors('to');
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

    private function postedCharge(
        School $school,
        Enrolment $enrolment,
        User $creator,
        int $amountMinor,
        string $chargedOn,
        string $description = 'Tuition',
    ): FeeCharge {
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => $amountMinor]);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'created_by_user_id' => $creator->id,
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => $amountMinor,
            'posted_at' => now(),
        ]);

        return FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($enrolment)->create([
            'description' => $description,
            'amount_minor' => $amountMinor,
            'status' => 'posted',
            'charged_on' => $chargedOn,
        ]);
    }

    private function recordReceipt(School $school, User $actor, int $amountMinor, string $receivedOn): SchoolReceipt
    {
        return app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'statement-receipt-'.str()->uuid(),
            'currency' => 'KES',
            'amount_minor' => $amountMinor,
            'received_on' => $receivedOn,
            'verification_note' => null,
        ]);
    }
}
