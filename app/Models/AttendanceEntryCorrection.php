<?php

namespace App\Models;

use Database\Factories\AttendanceEntryCorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEntryCorrection extends Model
{
    /** @use HasFactory<AttendanceEntryCorrectionFactory> */
    use HasFactory;

    protected $fillable = ['attendance_entry_id', 'attendance_session_id', 'from_status', 'to_status', 'reason', 'corrected_by_user_id', 'session_version', 'corrected_at'];

    protected function casts(): array
    {
        return ['corrected_at' => 'datetime'];
    }

    public function attendanceEntry(): BelongsTo
    {
        return $this->belongsTo(AttendanceEntry::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
