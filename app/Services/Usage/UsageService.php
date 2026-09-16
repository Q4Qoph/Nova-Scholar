<?php

namespace App\Services\Usage;

use App\Exceptions\EntitlementDenied;
use App\Models\UsageRecord;
use App\Models\UsageReservation;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Support\Facades\DB;
use LogicException;

class UsageService
{
    public function __construct(private EntitlementService $entitlements) {}

    public function release(UsageReservation $reservation): UsageReservation
    {
        return DB::transaction(function () use ($reservation): UsageReservation {
            $reservation = UsageReservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->status === 'settled') {
                throw new LogicException('Settled usage cannot be released.');
            }

            if ($reservation->status === 'pending') {
                $reservation->forceFill([
                    'status' => 'released',
                    'released_at' => now(),
                ])->save();
            }

            return $reservation;
        });
    }

    public function reserve(User $user, string $featureCode, int $quantity, string $requestKey): UsageReservation
    {
        if ($quantity < 1) {
            throw new LogicException('Usage quantity must be at least one.');
        }

        return DB::transaction(function () use ($user, $featureCode, $quantity, $requestKey): UsageReservation {
            $existingReservation = UsageReservation::query()
                ->where('request_key', $requestKey)
                ->lockForUpdate()
                ->first();

            if ($existingReservation !== null) {
                if ($existingReservation->user_id !== $user->id || $existingReservation->feature_code !== $featureCode) {
                    throw new LogicException('Usage request key belongs to a different operation.');
                }

                return $existingReservation;
            }

            $period = $this->entitlements->currentPeriodFor($user, lockForUpdate: true);
            $feature = $period?->subscription->plan->features->firstWhere('feature_code', $featureCode);

            if ($period === null || $feature === null) {
                throw new EntitlementDenied('This feature is not included in the current entitlement.');
            }

            if ($feature->allowance !== null) {
                $reservedQuantity = UsageReservation::query()
                    ->where('subscription_period_id', $period->id)
                    ->where('feature_code', $featureCode)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->sum('quantity');

                $settledQuantity = UsageRecord::query()
                    ->where('subscription_period_id', $period->id)
                    ->where('feature_code', $featureCode)
                    ->sum('quantity');

                if ($reservedQuantity + $settledQuantity + $quantity > $feature->allowance) {
                    throw new EntitlementDenied('The feature allowance has been exhausted.');
                }
            }

            $reservation = new UsageReservation([
                'feature_code' => $featureCode,
                'request_key' => $requestKey,
                'quantity' => $quantity,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15),
            ]);

            $reservation->user()->associate($user);
            $reservation->period()->associate($period);
            $reservation->save();

            return $reservation;
        });
    }

    public function settle(UsageReservation $reservation, int $actualQuantity, ?int $actualCostMinor = null, ?string $currency = null): UsageReservation
    {
        if ($actualQuantity < 0 || $actualQuantity > $reservation->quantity) {
            throw new LogicException('Actual usage must be between zero and the reserved quantity.');
        }

        return DB::transaction(function () use ($reservation, $actualQuantity, $actualCostMinor, $currency): UsageReservation {
            $reservation = UsageReservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->status === 'released') {
                throw new LogicException('Released usage cannot be settled.');
            }

            if ($reservation->status === 'settled') {
                return $reservation;
            }

            $record = new UsageRecord([
                'feature_code' => $reservation->feature_code,
                'quantity' => $actualQuantity,
                'actual_cost_minor' => $actualCostMinor,
                'currency' => $currency,
                'occurred_at' => now(),
            ]);

            $record->reservation()->associate($reservation);
            $record->user()->associate($reservation->user);
            $record->period()->associate($reservation->period);
            $record->save();

            $reservation->forceFill([
                'status' => 'settled',
                'settled_at' => now(),
            ])->save();

            return $reservation;
        });
    }
}
