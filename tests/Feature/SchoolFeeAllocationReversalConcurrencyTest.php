<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeReceiptAllocationReversal;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\ReverseSchoolReceiptAllocation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolFeeAllocationReversalConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent allocation reversal coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_only_one_concurrent_reversal_of_an_allocation_commits(): void
    {
        $school = School::factory()->create();
        $actor = $this->addSchoolAdmin($school);
        $learner = Enrolment::factory()->for($school)->create();
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000]);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'created_by_user_id' => $actor->id,
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => 10000,
            'posted_at' => now(),
        ]);
        $charge = FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($learner)->create([
            'amount_minor' => 10000,
            'status' => 'posted',
        ]);
        $receipt = app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'concurrent-reversal-receipt',
            'currency' => 'KES',
            'amount_minor' => 4000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
        $allocation = app(AllocateSchoolReceipt::class)->handle($actor, $school, $receipt->id, $charge->id, 4000, 'concurrent-reversal-allocation');

        $schoolId = $school->id;
        $allocationId = $allocation->id;
        $actorId = $actor->id;
        DB::purge();

        $parentSockets = [];
        $processIds = [];

        foreach (['concurrent-reversal-first', 'concurrent-reversal-second'] as $reversalKey) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Unable to create allocation-reversal worker sockets.');
            }

            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork an allocation-reversal worker.');
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
                    $worker = User::query()->findOrFail($actorId);
                    $workerSchool = School::query()->findOrFail($schoolId);
                    app(ReverseSchoolReceiptAllocation::class)->handle(
                        $worker,
                        $workerSchool,
                        $allocationId,
                        'Only one reversal may win.',
                        $reversalKey,
                    );
                    fwrite($childSocket, "reversed\n");
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

        $this->assertSame(['rejected', 'reversed'], $results);
        $this->assertSame(1, FeeReceiptAllocationReversal::query()->where('school_id', $schoolId)->count());
        $this->assertSame(3, DB::table('audit_events')->where('school_id', $schoolId)->count());
        $this->assertSame(10000, FeeCharge::query()->findOrFail($charge->id)->outstandingMinor());
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
}
