<?php

namespace App\Models;

use Database\Factories\PlanPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPrice extends Model
{
    /** @use HasFactory<PlanPriceFactory> */
    use HasFactory;

    protected $fillable = ['amount_minor', 'currency', 'billing_interval', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
