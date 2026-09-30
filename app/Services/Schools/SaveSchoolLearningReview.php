<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolLearningReview;
use App\Models\SchoolLearningSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveSchoolLearningReview
{
    /** @param array{feedback: mixed, score: mixed, maximum_score: mixed} $attributes */
    public function handle(User $actor, School $school, SchoolLearningSubmission $submission, array $attributes): SchoolLearningReview
    {
        return DB::transaction(function () use ($actor, $school, $submission, $attributes): SchoolLearningReview {
            School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $assignment = $school->learningAssignments()->whereKey($submission->school_learning_assignment_id)->lockForUpdate()->firstOrFail();
            $lockedSubmission = $assignment->submissions()->where('school_learning_submissions.school_id', $school->id)
                ->where('school_learning_submissions.id', $submission->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('review', $lockedSubmission);
            $review = $lockedSubmission->review()->lockForUpdate()->first();

            if ($review?->released_at !== null) {
                throw ValidationException::withMessages(['reviewFeedback' => 'Released feedback cannot be changed.']);
            }

            $attributes['feedback'] = is_string($attributes['feedback'] ?? null) ? trim($attributes['feedback']) : ($attributes['feedback'] ?? null);
            $attributes['score'] = ($attributes['score'] ?? null) === '' ? null : ($attributes['score'] ?? null);
            $attributes['maximum_score'] = ($attributes['maximum_score'] ?? null) === '' ? null : ($attributes['maximum_score'] ?? null);
            $validator = Validator::make($attributes, [
                'feedback' => ['required', 'string', 'max:10000'],
                'score' => ['nullable', 'required_with:maximum_score', 'integer', 'min:0', 'max:1000000'],
                'maximum_score' => ['nullable', 'required_with:score', 'integer', 'min:1', 'max:1000000'],
            ]);
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $errors[match ($field) {
                        'feedback' => 'reviewFeedback',
                        'score' => 'reviewScore',
                        'maximum_score' => 'reviewMaximumScore',
                    }] = $messages;
                }
                throw ValidationException::withMessages($errors);
            }
            $validated = $validator->validated();
            if ($validated['score'] !== null && (int) $validated['score'] > (int) $validated['maximum_score']) {
                throw ValidationException::withMessages(['reviewScore' => 'The score cannot exceed the maximum score.']);
            }

            $review = $lockedSubmission->review()->updateOrCreate([], [
                'school_id' => $school->id,
                'reviewed_by_user_id' => $actor->id,
                'feedback' => $validated['feedback'],
                'score' => $validated['score'],
                'maximum_score' => $validated['maximum_score'],
            ]);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_learning_review.draft_saved',
                'auditable_type' => SchoolLearningReview::class,
                'auditable_id' => $review->id,
                'metadata' => ['submission_id' => $lockedSubmission->id],
                'occurred_at' => now(),
            ]);

            return $review;
        });
    }
}
