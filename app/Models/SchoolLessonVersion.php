<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLessonVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolLessonVersion extends Model
{
    /** @use HasFactory<SchoolLessonVersionFactory> */
    use HasFactory;

    protected $fillable = ['school_lesson_id', 'version_number', 'title', 'body', 'status', 'created_by_user_id', 'published_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return [
            'status' => SchoolLessonVersionStatus::class,
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(SchoolLesson::class, 'school_lesson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(SchoolLessonResource::class);
    }
}
