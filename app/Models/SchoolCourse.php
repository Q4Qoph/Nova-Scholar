<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolCourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolCourse extends Model
{
    /** @use HasFactory<SchoolCourseFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'teaching_assignment_id', 'created_by_user_id', 'title', 'status'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SchoolLesson::class)->orderBy('position');
    }
}
