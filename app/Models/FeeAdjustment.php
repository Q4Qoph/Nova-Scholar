<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAdjustment extends Model
{
    protected $fillable = [
        'school_id', 'fee_charge_id', 'requested_by_user_id', 'reviewed_by_user_id',
        'adjustment_key', 'kind', 'status', 'amount_minor', 'reason', 'review_note',
        'requested_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'requested_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(FeeCharge::class, 'fee_charge_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
