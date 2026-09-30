<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveSchoolLearningSubmissionRequest;
use App\Models\SchoolLearningAssignment;
use App\Services\Schools\BuildLearnerTaskSummary;
use App\Services\Schools\SaveSchoolLearningSubmissionDraft;
use App\Services\Schools\SubmitSchoolLearningAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnerSchoolAssignmentController extends Controller
{
    public function index(Request $request, BuildLearnerTaskSummary $summary): View
    {
        $profileId = $request->user()->learnerProfile?->id;
        $assignments = $summary->assignments($request->user())
            ->select(['id', 'school_id', 'teaching_assignment_id', 'title', 'due_at', 'cutoff_at'])
            ->with(['school', 'teachingAssignment.subject', 'teachingAssignment.classGroup',
                'recipients' => fn ($query) => $query->where('learner_profile_id', $profileId)
                    ->with(['submission' => fn ($submission) => $submission
                        ->select(['id', 'school_id', 'school_learning_assignment_recipient_id', 'status', 'is_late'])
                        ->with(['releasedReview' => fn ($review) => $review
                            ->select(['id', 'school_id', 'school_learning_submission_id', 'released_at'])])]),
            ])->orderBy('due_at')->orderBy('id')->paginate(20);

        return view('learner.assignments.index', ['assignments' => $assignments]);
    }

    public function show(Request $request, SchoolLearningAssignment $assignment): View
    {
        abort_unless(Gate::allows('viewForLearner', $assignment), 404);

        $assignment->load([
            'teachingAssignment.subject',
            'teachingAssignment.classGroup',
            'sourceLessonVersion.lesson',
        ]);

        $profile = $request->user()->learnerProfile;
        abort_unless($profile !== null, 404);
        $recipient = $assignment->recipients()
            ->where('learner_profile_id', $profile->id)
            ->whereHas('enrolment', fn ($query) => $query->where('status', 'active')->where('school_id', $assignment->school_id))
            ->with(['submission.releasedReview' => fn ($query) => $query->where('school_id', $assignment->school_id)])
            ->firstOrFail();

        return view('learner.assignments.show', ['assignment' => $assignment, 'recipient' => $recipient]);
    }

    public function saveDraft(
        SaveSchoolLearningSubmissionRequest $request,
        SchoolLearningAssignment $assignment,
        SaveSchoolLearningSubmissionDraft $saveSchoolLearningSubmissionDraft,
    ): RedirectResponse {
        $saveSchoolLearningSubmissionDraft->handle(
            $request->user(),
            $assignment,
            $request->validated()['response_text'],
        );

        return to_route('learner.assignments.show', $assignment)->with('status', 'Draft saved.');
    }

    public function submit(
        SaveSchoolLearningSubmissionRequest $request,
        SchoolLearningAssignment $assignment,
        SubmitSchoolLearningAssignment $submitSchoolLearningAssignment,
    ): RedirectResponse {
        $submitSchoolLearningAssignment->handle(
            $request->user(),
            $assignment,
            $request->validated()['response_text'],
        );

        return to_route('learner.assignments.show', $assignment)->with('status', 'Your response was submitted.');
    }
}
