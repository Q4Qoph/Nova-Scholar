<?php

namespace App\Models;

use Database\Factories\LearnerActivationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerActivation extends Model
{
    /** @use HasFactory<LearnerActivationFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'learner_profile_id', 'issued_by_user_id', 'token_hash', 'expires_at', 'used_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function learnerProfile(): BelongsTo
    {
        return $this->belongsTo(LearnerProfile::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
