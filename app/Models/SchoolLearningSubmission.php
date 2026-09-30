<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLearningSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolLearningSubmission extends Model
{
    /** @use HasFactory<SchoolLearningSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'school_learning_assignment_id',
        'school_learning_assignment_recipient_id',
        'learner_profile_id',
        'submitted_by_user_id',
        'response_text',
        'status',
        'is_late',
        'draft_saved_at',
        'submitted_at',
        'acknowledgement_reference',
    ];

    protected function casts(): array
    {
        return [
            'is_late' => 'boolean',
            'draft_saved_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function review(): HasOne
    {
        return $this->hasOne(SchoolLearningReview::class);
    }

    public function releasedReview(): HasOne
    {
        return $this->review()->whereNotNull('released_at');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SchoolLearningAssignment::class, 'school_learning_assignment_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(SchoolLearningAssignmentRecipient::class, 'school_learning_assignment_recipient_id');
    }

    public function learnerProfile(): BelongsTo
    {
        return $this->belongsTo(LearnerProfile::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }
}
