<?php

namespace App\Models;

use Database\Factories\FeeChargeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
