<?php

namespace App\Models;

use App\SchoolRole;
use Database\Factories\SchoolInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolInvitation extends Model
{
    /** @use HasFactory<SchoolInvitationFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'inviter_user_id', 'invitee_user_id', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['role' => SchoolRole::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }
}
