<?php

namespace App\Models;

use Database\Factories\AttendanceSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    /** @use HasFactory<AttendanceSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_group_id',
        'teaching_assignment_id',
        'session_date',
        'status',
        'version',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceEntry::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceEntryCorrection::class);
    }
}
