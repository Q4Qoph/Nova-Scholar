<?php

namespace App\Models;

use Database\Factories\UsageReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UsageReservation extends Model
{
    /** @use HasFactory<UsageReservationFactory> */
    use HasFactory;

    protected $fillable = ['feature_code', 'request_key', 'quantity', 'status', 'expires_at', 'settled_at', 'released_at'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'expires_at' => 'datetime', 'settled_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPeriod::class, 'subscription_period_id');
    }

    public function record(): HasOne
    {
        return $this->hasOne(UsageRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
