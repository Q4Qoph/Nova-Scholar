<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeAdjustment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\RequestSchoolFeeCredit;
use App\Services\Schools\ReviewSchoolFeeCredit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolFeeCreditConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent credit review coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_only_one_concurrent_credit_can_approve_against_the_same_charge_balance(): void
    {
        $school = School::factory()->create();
        $requester = $this->addSchoolAdmin($school);
        $firstReviewer = $this->addSchoolAdmin($school);
        $secondReviewer = $this->addSchoolAdmin($school);
        $charge = $this->postedCharge($school, $requester, 10000);
        $requesterService = app(RequestSchoolFeeCredit::class);
        $firstAdjustment = $requesterService->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-race-first',
            'amount_minor' => 7000,
            'reason' => 'First competing credit',
        ]);
        $secondAdjustment = $requesterService->handle($requester, $school, $charge->id, [
            'adjustment_key' => 'credit-race-second',
            'amount_minor' => 7000,
            'reason' => 'Second competing credit',
        ]);

        $schoolId = $school->id;
        $reviewJobs = [
            ['actor_id' => $firstReviewer->id, 'adjustment_id' => $firstAdjustment->id],
            ['actor_id' => $secondReviewer->id, 'adjustment_id' => $secondAdjustment->id],
        ];
        DB::purge();

        $parentSockets = [];
        $processIds = [];

        foreach ($reviewJobs as $reviewJob) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Unable to create credit-review worker sockets.');
            }

            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork a credit-review worker.');
            }

            if ($processId === 0) {
                fclose($parentSocket);

                foreach ($parentSockets as $inheritedParentSocket) {
                    fclose($inheritedParentSocket);
                }

                if (fgets($childSocket) !== "go\n") {
                    fclose($childSocket);
                    exit(2);
                }

                try {
                    DB::reconnect();
                    $actor = User::query()->findOrFail($reviewJob['actor_id']);
                    $workerSchool = School::query()->findOrFail($schoolId);
                    app(ReviewSchoolFeeCredit::class)->handle(
                        $actor,
                        $workerSchool,
                        $reviewJob['adjustment_id'],
                        'approve',
                        null,
                    );
                    fwrite($childSocket, "approved\n");
                } catch (ValidationException) {
                    fwrite($childSocket, "rejected\n");
                } catch (Throwable $exception) {
                    fwrite($childSocket, 'failed:'.$exception::class."\n");
                }

                fclose($childSocket);
                exit(0);
            }

            fclose($childSocket);
            $parentSockets[] = $parentSocket;
            $processIds[] = $processId;
        }

        foreach ($parentSockets as $parentSocket) {
            fwrite($parentSocket, "go\n");
        }

        $results = [];

        foreach ($parentSockets as $parentSocket) {
            $results[] = trim((string) fgets($parentSocket));
            fclose($parentSocket);
        }

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }

        DB::reconnect();
        sort($results);

        $this->assertSame(['approved', 'rejected'], $results);
        $this->assertSame(1, FeeAdjustment::query()->where('school_id', $schoolId)->where('status', 'approved')->count());
        $this->assertSame(1, FeeAdjustment::query()->where('school_id', $schoolId)->where('status', 'pending')->count());
        $this->assertSame(3000, FeeCharge::query()->findOrFail($charge->id)->outstandingMinor());
        $this->assertSame(3, DB::table('audit_events')->where('school_id', $schoolId)->count());
    }

    private function addSchoolAdmin(School $school): User
    {
        $user = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

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
