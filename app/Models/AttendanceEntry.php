<?php

namespace App\Models;

use Database\Factories\AttendanceEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceEntry extends Model
{
    /** @use HasFactory<AttendanceEntryFactory> */
    use HasFactory;

    protected $fillable = ['attendance_session_id', 'enrolment_id', 'status', 'marked_at', 'marked_by_user_id'];

    protected function casts(): array
    {
        return ['marked_at' => 'datetime'];
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceEntryCorrection::class);
    }
}
