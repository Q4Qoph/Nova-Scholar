<?php

namespace App\Models;

use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'name', 'grade_level', 'stream', 'status'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function learnerClassMemberships(): HasMany
    {
        return $this->hasMany(LearnerClassMembership::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
