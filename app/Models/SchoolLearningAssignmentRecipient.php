<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLearningAssignmentRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolLearningAssignmentRecipient extends Model
{
    /** @use HasFactory<SchoolLearningAssignmentRecipientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'school_learning_assignment_id',
        'learner_profile_id',
        'enrolment_id',
        'learner_class_membership_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SchoolLearningAssignment::class, 'school_learning_assignment_id');
    }

    public function learnerProfile(): BelongsTo
    {
        return $this->belongsTo(LearnerProfile::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function classMembership(): BelongsTo
    {
        return $this->belongsTo(LearnerClassMembership::class, 'learner_class_membership_id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(SchoolLearningSubmission::class);
    }
}
