<?php

namespace App\Models;

use Database\Factories\FeeScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeSchedule extends Model
{
    /** @use HasFactory<FeeScheduleFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'term_id', 'class_group_id', 'name', 'currency', 'amount_minor', 'starts_on', 'ends_on', 'status'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function chargeBatches(): HasMany
    {
        return $this->hasMany(FeeChargeBatch::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(FeeCharge::class);
    }
}
