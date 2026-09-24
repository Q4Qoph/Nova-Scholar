<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AdmitLearner;
use App\Services\Schools\PostFeeChargeBatch;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolFeePostingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent fee posting coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_post_waits_for_a_roster_write_and_rejects_the_stale_preview(): void
    {
        $actor = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $actor->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        Enrolment::factory()->for($school)->create(['admission_number' => 'POST-RACE-001']);
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000]);
        $batch = app(PostFeeChargeBatch::class)->preview($actor, $school, $schedule, 'posting-roster-race');

        $lockSockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $postSockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($lockSockets === false || $postSockets === false) {
            throw new RuntimeException('Unable to create fee-posting worker sockets.');
        }

        [$lockParentSocket, $lockChildSocket] = $lockSockets;
        [$postParentSocket, $postChildSocket] = $postSockets;
        DB::purge();
        $lockProcessId = pcntl_fork();

        if ($lockProcessId === -1) {
            throw new RuntimeException('Unable to fork a roster worker.');
        }

        if ($lockProcessId === 0) {
            fclose($lockParentSocket);
            fclose($postParentSocket);
            fclose($postChildSocket);

            try {
                DB::reconnect();
                DB::beginTransaction();
                School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
                fwrite($lockChildSocket, "locked\n");
                fgets($lockChildSocket);
                app(AdmitLearner::class)->handle($actor, School::query()->findOrFail($school->id), [
                    'first_name' => 'New',
                    'last_name' => 'Learner',
                    'preferred_name' => null,
                    'date_of_birth' => null,
                    'admission_number' => 'POST-RACE-002',
                ]);
                DB::commit();
                fwrite($lockChildSocket, "admitted\n");
            } catch (Throwable $exception) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                fwrite($lockChildSocket, 'failed:'.$exception::class."\n");
            }

            fclose($lockChildSocket);
            exit(0);
        }

        fclose($lockChildSocket);
        $this->assertSame("locked\n", fgets($lockParentSocket));
        $postProcessId = pcntl_fork();

        if ($postProcessId === -1) {
            fwrite($lockParentSocket, "release\n");
            pcntl_waitpid($lockProcessId, $lockStatus);
            throw new RuntimeException('Unable to fork a fee-posting worker.');
        }

        if ($postProcessId === 0) {
            fclose($lockParentSocket);
            fclose($postParentSocket);

            try {
                DB::reconnect();
                $backendPid = DB::selectOne('select pg_backend_pid() as pid')->pid;
                fwrite($postChildSocket, $backendPid."\n");
                $workerActor = User::query()->findOrFail($actor->id);
                $workerSchool = School::query()->findOrFail($school->id);
                $workerSchedule = FeeSchedule::query()->findOrFail($schedule->id);
                app(PostFeeChargeBatch::class)->handle($workerActor, $workerSchool, $workerSchedule, $batch->batch_key);
                fwrite($postChildSocket, "posted\n");
            } catch (ValidationException $exception) {
                $stalePreviewMessage = $exception->errors()['batch_key'][0] ?? 'Unexpected validation failure.';
                fwrite($postChildSocket, $stalePreviewMessage."\n");
            } catch (Throwable $exception) {
                fwrite($postChildSocket, 'failed:'.$exception::class."\n");
            }

            fclose($postChildSocket);
            exit(0);
        }

        fclose($postChildSocket);
        $backendPid = (int) trim((string) fgets($postParentSocket));
        DB::reconnect();
        $waitEventType = null;

        for ($attempt = 0; $attempt < 500; $attempt++) {
            $activity = DB::selectOne('select wait_event_type from pg_stat_activity where pid = ?', [$backendPid]);
            $waitEventType = $activity?->wait_event_type;

            if ($waitEventType === 'Lock') {
                break;
            }

            usleep(10000);
        }

        fwrite($lockParentSocket, "release\n");
        $admissionResult = trim((string) fgets($lockParentSocket));
        $postingResult = trim((string) fgets($postParentSocket));
        fclose($lockParentSocket);
        fclose($postParentSocket);

        pcntl_waitpid($lockProcessId, $lockStatus);
        pcntl_waitpid($postProcessId, $postStatus);

        $this->assertSame('Lock', $waitEventType, 'The posting worker should wait for the school-row lock.');
        $this->assertSame('admitted', $admissionResult);
        $this->assertSame('The schedule or eligible learners changed. Preview this batch again before posting it.', $postingResult);
        $this->assertSame(0, pcntl_wexitstatus($lockStatus));
        $this->assertSame(0, pcntl_wexitstatus($postStatus));
        $this->assertDatabaseCount('enrolments', 2);
        $this->assertDatabaseCount('fee_charges', 0);
        $this->assertDatabaseHas('fee_charge_batches', ['id' => $batch->id, 'status' => 'draft']);
        $this->assertDatabaseCount('audit_events', 1);
    }
}
