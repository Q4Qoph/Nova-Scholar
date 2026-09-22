<?php

namespace App\Models;

use Database\Factories\LearnerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerProfile extends Model
{
    /** @use HasFactory<LearnerProfileFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'first_name', 'last_name', 'preferred_name', 'date_of_birth', 'status'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
