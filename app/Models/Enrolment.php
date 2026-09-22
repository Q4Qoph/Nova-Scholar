<?php

namespace App\Models;

use Database\Factories\EnrolmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrolment extends Model
{
    /** @use HasFactory<EnrolmentFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'learner_profile_id', 'admission_number', 'status', 'enrolled_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return ['enrolled_at' => 'date', 'withdrawn_at' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function learnerProfile(): BelongsTo
    {
        return $this->belongsTo(LearnerProfile::class);
    }

    public function guardianLinks(): HasMany
    {
        return $this->hasMany(GuardianLink::class);
    }

    public function classMemberships(): HasMany
    {
        return $this->hasMany(LearnerClassMembership::class);
    }
}
