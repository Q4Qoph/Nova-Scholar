<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolRefund extends Model
{
    protected $fillable = [
        'school_id', 'school_receipt_id', 'requested_by_user_id', 'reviewed_by_user_id',
        'completed_by_user_id', 'refund_key', 'completion_key', 'status', 'refund_method',
        'payout_reference', 'currency', 'amount_minor', 'reason', 'review_note',
        'requested_at', 'reviewed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(SchoolReceipt::class, 'school_receipt_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
