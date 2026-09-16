<?php

namespace App\Models;

use Database\Factories\SubscriptionPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPeriod extends Model
{
    /** @use HasFactory<SubscriptionPeriodFactory> */
    use HasFactory;

    protected $fillable = ['status', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(UsageReservation::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
