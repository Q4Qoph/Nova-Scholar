<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Database\Eloquent\Builder;

class BuildSchoolSetupChecklist
{
    /** @return array<int, array{label: string, complete: bool, url: string, detail: string}> */
    public function handle(User $user, School $school): array
    {
        abort_unless($school->status === 'active' && $school->memberships()->active()->where('user_id', $user->id)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('role', SchoolRole::SchoolAdmin->value))->exists(), 403);
        $academicUrl = route('filament.school.pages.academic-structure', ['tenant' => $school->slug]);
        $registryUrl = route('filament.school.pages.learner-registry', ['tenant' => $school->slug]);
        $staffUrl = route('filament.school.pages.school-staff-directory', ['tenant' => $school->slug]);
        $learningUrl = route('filament.school.pages.school-learning', ['tenant' => $school->slug]);
        $teachers = $school->memberships()->active()->whereHas('roles', fn (Builder $query): Builder => $query->where('role', SchoolRole::Teacher->value));
        $assignments = TeachingAssignment::query()->where('school_id', $school->id)->where('status', 'active')
            ->whereIn('teacher_user_id', (clone $teachers)->select('user_id'))
            ->whereHas('classGroup', fn (Builder $query): Builder => $query->where('school_id', $school->id)->where('status', 'active'))
            ->whereHas('subject', fn (Builder $query): Builder => $query->where('school_id', $school->id)->where('status', 'active'));
        $placements = LearnerClassMembership::query()->where('school_id', $school->id)->where('status', 'active')
            ->whereDate('starts_on', '<=', today())->where(fn (Builder $query): Builder => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->whereIn('class_group_id', (clone $assignments)->select('class_group_id'))
            ->whereHas('enrolment', fn (Builder $query): Builder => $query->where('school_id', $school->id)->where('status', 'active')
                ->whereHas('learnerProfile', fn (Builder $profile): Builder => $profile->where('status', 'active')));
        $managed = (clone $placements)->whereHas('enrolment.learnerProfile.user', fn (Builder $query): Builder => $query
            ->where('account_type', 'managed_learner')->whereNotNull('learner_activated_at')->whereNull('learner_deactivated_at'));
        $published = SchoolLearningAssignment::query()->where('school_id', $school->id)->where('status', 'published')
            ->whereIn('teaching_assignment_id', (clone $assignments)->select('id'))
            ->whereHas('course', fn (Builder $query): Builder => $query->where('school_id', $school->id)->where('status', 'active'))
            ->whereHas('recipients', fn (Builder $query): Builder => $query->where('school_id', $school->id)
                ->whereHas('enrolment', fn (Builder $enrolment): Builder => $enrolment->where('school_id', $school->id)->where('status', 'active')
                    ->whereHas('classMemberships', fn (Builder $placement): Builder => $placement
                        ->whereIn('id', (clone $managed)->select('id'))
                        ->whereHas('classGroup.teachingAssignments', fn (Builder $teaching): Builder => $teaching->whereColumn('teaching_assignments.id', 'school_learning_assignments.teaching_assignment_id')))));

        return [
            ['label' => 'Academic year, term and class', 'complete' => $school->academicYears()->where('status', 'open')->whereHas('terms')->whereHas('classGroups', fn (Builder $query): Builder => $query->where('status', 'active'))->exists(), 'url' => $academicUrl.'#academic-setup', 'detail' => 'Set the year and term dates, then add a class.'],
            ['label' => 'Subjects', 'complete' => $school->subjects()->where('status', 'active')->exists(), 'url' => $academicUrl.'#subjects', 'detail' => 'Add the subject you will demonstrate.'],
            ['label' => 'Active teacher', 'complete' => $teachers->exists(), 'url' => $staffUrl, 'detail' => 'Invite a teacher and assign the school teacher role.'],
            ['label' => 'Teaching assignment', 'complete' => $assignments->exists(), 'url' => $academicUrl.'#teaching-assignments', 'detail' => 'Connect an active teacher, class and subject.'],
            ['label' => 'Learner in the assigned class', 'complete' => $placements->exists(), 'url' => $registryUrl, 'detail' => 'Admit or import a learner, then open their record to add a dated class placement.'],
            ['label' => 'Managed learner access', 'complete' => $managed->exists(), 'url' => $registryUrl, 'detail' => 'Open that learner record to activate their school login.'],
            ['label' => 'First published assignment', 'complete' => $published->exists(), 'url' => $learningUrl, 'detail' => 'Create a course and publish a text lesson and assignment for the same class.'],
            ['label' => 'Verified guardian (optional)', 'complete' => $school->guardianLinks()->where('status', 'active')->whereNotNull('verified_at')->whereNull('revoked_at')->whereHas('enrolment', fn (Builder $query): Builder => $query->where('status', 'active'))->exists(), 'url' => $registryUrl, 'detail' => 'Link and verify a guardian from the learner record.'],
        ];
    }
}
