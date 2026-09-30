<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SchoolCourse;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersionStatus;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnerSchoolCourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $courses = $this->coursesForLearner($user);

        return view('learner.courses.index', ['courses' => $courses]);
    }

    public function show(Request $request, SchoolCourse $course): View
    {
        abort_unless(Gate::allows('viewForLearner', $course), 404);

        $course->load([
            'teachingAssignment.classGroup.academicYear',
            'teachingAssignment.subject',
            'lessons.versions' => fn ($query) => $query
                ->where('status', SchoolLessonVersionStatus::Published->value)
                ->with(['resources' => fn ($resourceQuery) => $resourceQuery
                    ->where('status', SchoolLessonResourceStatus::Clean->value)
                    ->whereNull('bytes_purged_at')]),
        ]);

        return view('learner.courses.show', ['course' => $course]);
    }

    /** @return Collection<int, SchoolCourse> */
    private function coursesForLearner(User $user): Collection
    {
        $learnerProfile = $user->learnerProfile;
        if ($learnerProfile === null) {
            return SchoolCourse::query()->whereRaw('1 = 0')->get();
        }

        $today = now()->toDateString();
        $enrolments = $learnerProfile->enrolments()
            ->where('status', 'active')
            ->with(['classMemberships' => fn ($query) => $query
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $today)
                ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))])
            ->get();

        $placementPairs = [];
        foreach ($enrolments as $enrolment) {
            foreach ($enrolment->classMemberships as $placement) {
                $placementPairs[$enrolment->school_id][] = $placement->class_group_id;
            }
        }

        if ($placementPairs === []) {
            return SchoolCourse::query()->whereRaw('1 = 0')->get();
        }

        $assignmentQuery = TeachingAssignment::query()
            ->where('status', 'active')
            ->whereHas('classGroup', fn (Builder $query) => $query->where('status', 'active'))
            ->whereHas('school', fn (Builder $query) => $query->where('status', 'active'))
            ->where(function (Builder $query) use ($placementPairs): void {
                foreach ($placementPairs as $schoolId => $classGroupIds) {
                    $query->orWhere(fn (Builder $placementQuery) => $placementQuery
                        ->where('school_id', $schoolId)
                        ->whereIn('class_group_id', array_values(array_unique($classGroupIds))));
                }
            });

        $courses = SchoolCourse::query()
            ->where('status', 'active')
            ->whereIn('teaching_assignment_id', $assignmentQuery->select('id'))
            ->whereHas('lessons.versions', fn (Builder $query) => $query->where('status', SchoolLessonVersionStatus::Published->value))
            ->with(['teachingAssignment.classGroup', 'teachingAssignment.subject'])
            ->orderBy('title')
            ->get();

        return $courses->filter(fn (SchoolCourse $course): bool => Gate::allows('viewForLearner', $course))->values();
    }
}
