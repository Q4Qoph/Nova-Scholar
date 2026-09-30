<?php

namespace App\Models;

use Database\Factories\TeachingAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeachingAssignment extends Model
{
    /** @use HasFactory<TeachingAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_group_id', 'subject_id', 'teacher_user_id', 'status'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function lessonCourse(): HasOne
    {
        return $this->hasOne(SchoolCourse::class);
    }

    public function learningAssignments(): HasMany
    {
        return $this->hasMany(SchoolLearningAssignment::class);
    }
}
