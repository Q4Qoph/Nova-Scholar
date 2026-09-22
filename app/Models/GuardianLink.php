<?php

namespace App\Models;

use Database\Factories\GuardianLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianLink extends Model
{
    /** @use HasFactory<GuardianLinkFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'enrolment_id', 'guardian_user_id', 'relationship', 'status', 'verified_by_user_id', 'verified_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
