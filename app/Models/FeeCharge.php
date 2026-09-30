<?php

namespace App\Models;

use Database\Factories\FeeChargeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class FeeCharge extends Model
{
    /** @use HasFactory<FeeChargeFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'fee_charge_batch_id', 'fee_schedule_id', 'enrolment_id', 'description', 'currency', 'amount_minor', 'status', 'charged_on'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'charged_on' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeChargeBatch(): BelongsTo
    {
        return $this->belongsTo(FeeChargeBatch::class);
    }

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function receiptAllocations(): HasMany
    {
        return $this->hasMany(FeeReceiptAllocation::class);
    }

    public function receiptAllocationReversals(): HasManyThrough
    {
        return $this->hasManyThrough(
            FeeReceiptAllocationReversal::class,
            FeeReceiptAllocation::class,
            'fee_charge_id',
            'fee_receipt_allocation_id',
            'id',
            'id',
        );
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(FeeAdjustment::class);
    }

    public function outstandingMinor(): int
    {
        $allocatedMinor = $this->getAttribute('receipt_allocations_sum_amount_minor');
        $reversedMinor = $this->getAttribute('receipt_allocation_reversals_sum_amount_minor');
        $approvedCreditsMinor = $this->getAttribute('approved_credits_minor');

        $allocatedMinor ??= $this->receiptAllocations()->sum('amount_minor');
        $reversedMinor ??= $this->receiptAllocationReversals()->sum('fee_receipt_allocation_reversals.amount_minor');
        $approvedCreditsMinor ??= $this->adjustments()
            ->where('kind', 'credit')
            ->where('status', 'approved')
            ->sum('amount_minor');

        return max(0, $this->amount_minor - (int) $allocatedMinor + (int) $reversedMinor - (int) $approvedCreditsMinor);
    }
}
