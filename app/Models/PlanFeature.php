<?php

namespace App\Models;

use Database\Factories\PlanFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    /** @use HasFactory<PlanFeatureFactory> */
    use HasFactory;

    protected $fillable = ['feature_code', 'allowance'];

    protected function casts(): array
    {
        return ['allowance' => 'integer'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
