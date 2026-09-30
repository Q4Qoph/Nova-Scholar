<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\SchoolRefund;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolReceiptRefund;
use App\Services\Schools\ReviewSchoolReceiptRefund;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolReceiptRefundConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent refund review coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_only_one_concurrent_refund_can_reserve_the_same_receipt_balance(): void
    {
        $school = School::factory()->create();
        $requester = $this->addSchoolAdmin($school);
        $firstReviewer = $this->addSchoolAdmin($school);
        $secondReviewer = $this->addSchoolAdmin($school);
        $receipt = $this->recordReceipt($school, $requester, 10000);
        $requestService = app(RequestSchoolReceiptRefund::class);
        $firstRefund = $requestService->handle($requester, $school, $receipt->id, [
            'refund_key' => 'refund-race-first',
            'amount_minor' => 7000,
            'refund_method' => 'cash',
            'reason' => 'First competing refund',
        ]);
        $secondRefund = $requestService->handle($requester, $school, $receipt->id, [
            'refund_key' => 'refund-race-second',
            'amount_minor' => 7000,
            'refund_method' => 'cash',
            'reason' => 'Second competing refund',
        ]);

        $schoolId = $school->id;
        $reviewJobs = [
            ['actor_id' => $firstReviewer->id, 'refund_id' => $firstRefund->id],
            ['actor_id' => $secondReviewer->id, 'refund_id' => $secondRefund->id],
        ];
        DB::purge();

        $parentSockets = [];
        $processIds = [];

        foreach ($reviewJobs as $reviewJob) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Unable to create refund-review worker sockets.');
            }

            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork a refund-review worker.');
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
                    app(ReviewSchoolReceiptRefund::class)->handle(
                        $actor,
                        $workerSchool,
                        $reviewJob['refund_id'],
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
        $this->assertSame(1, SchoolRefund::query()->where('school_id', $schoolId)->where('status', 'approved')->count());
        $this->assertSame(1, SchoolRefund::query()->where('school_id', $schoolId)->where('status', 'pending')->count());
        $this->assertSame(3000, SchoolReceipt::query()->findOrFail($receipt->id)->availableMinor());
        $this->assertSame(4, DB::table('audit_events')->where('school_id', $schoolId)->count());
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

    private function recordReceipt(School $school, User $actor, int $amountMinor): SchoolReceipt
    {
        return app(RecordSchoolReceipt::class)->handle($actor, $school, [
            'source' => 'cash',
            'source_reference' => null,
            'submission_key' => 'refund-race-receipt',
            'currency' => 'KES',
            'amount_minor' => $amountMinor,
            'received_on' => today()->toDateString(),
            'verification_note' => null,
        ]);
    }
}
