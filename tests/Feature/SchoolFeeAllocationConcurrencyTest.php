<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\PostFeeChargeBatch;
use App\Services\Schools\RecordSchoolReceipt;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolFeeAllocationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent allocation coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_only_one_concurrent_allocation_can_spend_the_same_receipt_balance(): void
    {
        $actor = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $actor->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        Enrolment::factory()->for($school)->count(2)->create();
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 10000, 'currency' => 'KES']);
        $batch = app(PostFeeChargeBatch::class)->preview($actor, $school, $schedule, 'allocation-race');
        $postedBatch = app(PostFeeChargeBatch::class)->handle($actor, $school, $schedule, $batch->batch_key);
        $charges = $postedBatch->charges->all();
        $receipt = app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => '47a0beb2-4379-4d8e-89e0-4123cf2be96b',
            'currency' => 'KES',
            'amount_minor' => 10000,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);

        $actorId = $actor->id;
        $schoolId = $school->id;
        $receiptId = $receipt->id;
        $chargeIds = array_map(fn (FeeCharge $charge): int => $charge->id, $charges);
        $allocationKeys = [
            '37a0900c-b242-4c74-9a51-d498a9d8ea5f',
            'aa6ffef5-823b-4fca-b653-e6184ee25318',
        ];
        DB::purge();

        $parentSockets = [];
        $processIds = [];

        foreach ($chargeIds as $index => $chargeId) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Unable to create allocation worker sockets.');
            }

            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork an allocation worker.');
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
                    $workerActor = User::query()->findOrFail($actorId);
                    $workerSchool = School::query()->findOrFail($schoolId);
                    app(AllocateSchoolReceipt::class)->handle(
                        $workerActor,
                        $workerSchool,
                        $receiptId,
                        $chargeId,
                        7000,
                        $allocationKeys[$index],
                    );
                    fwrite($childSocket, "allocated\n");
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

        sort($results);

        $this->assertSame(['allocated', 'rejected'], $results);
        $this->assertDatabaseCount('fee_receipt_allocations', 1);
        $this->assertDatabaseHas('fee_receipt_allocations', [
            'school_receipt_id' => $receiptId,
            'amount_minor' => 7000,
        ]);
    }
}
