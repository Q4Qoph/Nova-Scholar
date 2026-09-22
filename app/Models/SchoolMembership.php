<?php

namespace App\Models;

use Database\Factories\SchoolMembershipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolMembership extends Model
{
    /** @use HasFactory<SchoolMembershipFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'user_id', 'status', 'joined_at', 'removed_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(SchoolRoleAssignment::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active')->whereNull('removed_at');
    }
}
