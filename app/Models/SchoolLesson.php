<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolLesson extends Model
{
    /** @use HasFactory<SchoolLessonFactory> */
    use HasFactory;

    protected $fillable = ['school_course_id', 'position'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(SchoolCourse::class, 'school_course_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SchoolLessonVersion::class);
    }
}
