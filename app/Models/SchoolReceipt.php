<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolReceipt extends Model
{
    protected $fillable = [
        'school_id', 'verified_by_user_id', 'source', 'source_reference', 'submission_key',
        'currency', 'amount_minor', 'received_on', 'verification_note', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'received_on' => 'date', 'verified_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FeeReceiptAllocation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SchoolRefund::class, 'school_receipt_id');
    }

    public function allocationReversals(): HasManyThrough
    {
        return $this->hasManyThrough(
            FeeReceiptAllocationReversal::class,
            FeeReceiptAllocation::class,
            'school_receipt_id',
            'fee_receipt_allocation_id',
            'id',
            'id',
        );
    }

    public function availableMinor(): int
    {
        $allocatedMinor = $this->getAttribute('allocations_sum_amount_minor');
        $reversedMinor = $this->getAttribute('allocation_reversals_sum_amount_minor');
        $reservedRefundMinor = $this->getAttribute('reserved_refunds_sum_amount_minor');

        $allocatedMinor ??= $this->allocations()->sum('amount_minor');
        $reversedMinor ??= $this->allocationReversals()->sum('fee_receipt_allocation_reversals.amount_minor');
        $reservedRefundMinor ??= $this->refunds()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount_minor');

        return max(0, $this->amount_minor - (int) $allocatedMinor + (int) $reversedMinor - (int) $reservedRefundMinor);
    }
}
