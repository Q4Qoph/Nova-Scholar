<?php

namespace App\Models;

use Database\Factories\UsageRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    /** @use HasFactory<UsageRecordFactory> */
    use HasFactory;

    protected $fillable = ['feature_code', 'quantity', 'estimated_cost_minor', 'actual_cost_minor', 'currency', 'occurred_at'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'estimated_cost_minor' => 'integer', 'actual_cost_minor' => 'integer', 'occurred_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(UsageReservation::class, 'usage_reservation_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPeriod::class, 'subscription_period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
