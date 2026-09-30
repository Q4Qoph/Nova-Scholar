<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeReceiptAllocationReversal extends Model
{
    protected $fillable = [
        'school_id', 'fee_receipt_allocation_id', 'reversed_by_user_id',
        'reversal_key', 'amount_minor', 'reason', 'reversed_at',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'reversed_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(FeeReceiptAllocation::class, 'fee_receipt_allocation_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }
}
