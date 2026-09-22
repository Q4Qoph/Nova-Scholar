<?php

namespace App\Models;

use Database\Factories\FeeChargeBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeChargeBatch extends Model
{
    /** @use HasFactory<FeeChargeBatchFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'fee_schedule_id', 'created_by_user_id', 'batch_key', 'status', 'eligible_count', 'total_minor', 'posted_at'];

    protected function casts(): array
    {
        return ['eligible_count' => 'integer', 'total_minor' => 'integer', 'posted_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(FeeCharge::class);
    }
}
