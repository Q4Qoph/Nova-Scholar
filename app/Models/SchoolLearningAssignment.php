<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLearningAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolLearningAssignment extends Model
{
    /** @use HasFactory<SchoolLearningAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'teaching_assignment_id',
        'school_course_id',
        'source_lesson_version_id',
        'created_by_user_id',
        'title',
        'instructions',
        'submission_type',
        'due_at',
        'cutoff_at',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'cutoff_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(SchoolCourse::class, 'school_course_id');
    }

    public function sourceLessonVersion(): BelongsTo
    {
        return $this->belongsTo(SchoolLessonVersion::class, 'source_lesson_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(SchoolLearningAssignmentRecipient::class);
    }

    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            SchoolLearningSubmission::class,
            SchoolLearningAssignmentRecipient::class,
            'school_learning_assignment_id',
            'school_learning_assignment_recipient_id',
        );
    }
}
