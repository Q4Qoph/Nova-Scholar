<?php

namespace App\Services\Entitlements;

use App\Models\SubscriptionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EntitlementService
{
    public function currentPeriodFor(User $user, bool $lockForUpdate = false): ?SubscriptionPeriod
    {
        $periodQuery = SubscriptionPeriod::query()
            ->with('subscription.plan.features')
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->whereHas('subscription', function (Builder $subscriptionQuery) use ($user): void {
                $subscriptionQuery
                    ->whereBelongsTo($user)
                    ->where('status', 'active')
                    ->whereHas('plan', fn (Builder $planQuery): Builder => $planQuery->where('is_active', true));
            })
            ->orderByDesc('ends_at')
            ->orderByDesc('id');

        if ($lockForUpdate) {
            $periodQuery->lockForUpdate();
        }

        return $periodQuery->first();
    }

    public function allowanceFor(User $user, string $featureCode): ?int
    {
        $period = $this->currentPeriodFor($user);
        $feature = $period?->subscription->plan->features->firstWhere('feature_code', $featureCode);

        return $feature?->allowance;
    }
}
