<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\FeeSchedule;
use App\Models\GuardianLink;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $password = env('NOVA_DEMO_PASSWORD', 'ChangeMe123!');
        $platformAdmin = $this->user('platform-admin@nova.test', 'Demo Platform Admin', UserRole::Admin, $password);
        $schoolAdmin = $this->user('school-admin@nova.test', 'Demo School Admin', UserRole::Student, $password);
        $teacher = $this->user('teacher@nova.test', 'Demo Teacher', UserRole::Student, $password);
        $guardian = $this->user('guardian@nova.test', 'Demo Guardian', UserRole::Student, $password);
        $school = School::query()->updateOrCreate(
            ['slug' => 'demo-school'],
            ['name' => 'Nova Demo School', 'school_type' => 'mixed', 'status' => 'active', 'timezone' => 'Africa/Nairobi'],
        );

        $this->membership($school, $schoolAdmin, SchoolRole::SchoolAdmin);
        $this->membership($school, $teacher, SchoolRole::Teacher);
        $this->membership($school, $guardian, SchoolRole::Guardian);

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['school_id' => $school->id, 'name' => '2026 Demo Year'],
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'open'],
        );
        $term = Term::query()->updateOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Term 1'],
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-04-30', 'status' => 'open'],
        );
        $classGroup = ClassGroup::query()->updateOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Grade 5 Demo'],
            ['grade_level' => 'Grade 5', 'stream' => 'A', 'status' => 'active'],
        );
        $subject = Subject::query()->updateOrCreate(
            ['school_id' => $school->id, 'code' => 'DEMO-MATH'],
            ['name' => 'Demo Mathematics', 'status' => 'active'],
        );
        TeachingAssignment::query()->updateOrCreate(
            ['school_id' => $school->id, 'class_group_id' => $classGroup->id, 'subject_id' => $subject->id, 'teacher_user_id' => $teacher->id],
            ['status' => 'active'],
        );

        $profile = LearnerProfile::query()->firstOrCreate(['first_name' => 'Demo', 'last_name' => 'Learner'], ['status' => 'active']);
        $enrolment = Enrolment::query()->updateOrCreate(
            ['school_id' => $school->id, 'admission_number' => 'DEMO-001'],
            ['learner_profile_id' => $profile->id, 'status' => 'active', 'enrolled_at' => '2026-01-01', 'withdrawn_at' => null],
        );
        LearnerClassMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'class_group_id' => $classGroup->id],
            ['starts_on' => '2026-01-01', 'ends_on' => null, 'status' => 'active'],
        );
        GuardianLink::query()->updateOrCreate(
            ['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'guardian_user_id' => $guardian->id],
            ['relationship' => 'parent', 'status' => 'active', 'verified_by_user_id' => $schoolAdmin->id, 'verified_at' => now(), 'revoked_at' => null],
        );
        FeeSchedule::query()->updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Demo Term 1 Tuition'],
            ['term_id' => $term->id, 'class_group_id' => $classGroup->id, 'currency' => 'KES', 'amount_minor' => 125000, 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30', 'status' => 'active'],
        );
    }

    private function user(string $email, string $name, UserRole $role, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => Hash::make($password), 'email_verified_at' => now()],
        );
    }

    private function membership(School $school, User $user, SchoolRole $role): SchoolMembership
    {
        $membership = SchoolMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $user->id],
            ['status' => 'active', 'joined_at' => now(), 'removed_at' => null],
        );
        $membership->roles()->updateOrCreate(['role' => $role]);

        return $membership;
    }
}
