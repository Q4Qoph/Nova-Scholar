<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'school_type', 'status', 'timezone'];

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SchoolInvitation::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    public function learners(): HasMany
    {
        return $this->enrolments();
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function terms(): HasManyThrough
    {
        return $this->hasManyThrough(Term::class, AcademicYear::class);
    }

    public function classGroups(): HasManyThrough
    {
        return $this->hasManyThrough(ClassGroup::class, AcademicYear::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function learnerClassMemberships(): HasMany
    {
        return $this->hasMany(LearnerClassMembership::class);
    }

    public function learnerActivations(): HasMany
    {
        return $this->hasMany(LearnerActivation::class);
    }

    public function guardianLinks(): HasMany
    {
        return $this->hasMany(GuardianLink::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceEntryCorrections(): HasMany
    {
        return $this->hasMany(AttendanceEntryCorrection::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function messageDeliveries(): HasMany
    {
        return $this->hasMany(MessageDelivery::class);
    }

    public function feeSchedules(): HasMany
    {
        return $this->hasMany(FeeSchedule::class);
    }

    public function feeChargeBatches(): HasMany
    {
        return $this->hasMany(FeeChargeBatch::class);
    }

    public function feeCharges(): HasMany
    {
        return $this->hasMany(FeeCharge::class);
    }
}
