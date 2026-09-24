<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeReceiptAllocation extends Model
{
    protected $fillable = [
        'school_id', 'school_receipt_id', 'fee_charge_id', 'allocated_by_user_id',
        'allocation_key', 'amount_minor', 'allocated_at',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'allocated_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(SchoolReceipt::class, 'school_receipt_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(FeeCharge::class, 'fee_charge_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_user_id');
    }
}
