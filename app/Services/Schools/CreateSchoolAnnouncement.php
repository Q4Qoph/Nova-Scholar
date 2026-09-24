<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSchoolAnnouncement
{
    /**
     * @param  array{audience_type: string, class_group_id?: int|string|null, title: string, body: string}  $data
     */
    public function handle(User $actor, School $school, array $data): Announcement
    {
        Gate::forUser($actor)->authorize('create', [Announcement::class, $school]);

        $data['class_group_id'] = filled($data['class_group_id'] ?? null)
            ? $data['class_group_id']
            : null;

        $validated = Validator::make($data, [
            'audience_type' => ['required', Rule::in(['all_guardians', 'class_guardians'])],
            'class_group_id' => [
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('status', 'active')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ])->validate();

        $classGroupId = filled($validated['class_group_id'] ?? null)
            ? (int) $validated['class_group_id']
            : null;

        if ($validated['audience_type'] === 'class_guardians' && $classGroupId === null) {
            throw ValidationException::withMessages([
                'class_group_id' => 'Select an active class for a class notice.',
            ]);
        }

        if ($validated['audience_type'] === 'all_guardians' && $classGroupId !== null) {
            throw ValidationException::withMessages([
                'class_group_id' => 'A school-wide notice cannot include a class.',
            ]);
        }

        return DB::transaction(fn (): Announcement => $school->announcements()->create([
            'created_by_user_id' => $actor->id,
            'audience_type' => $validated['audience_type'],
            'class_group_id' => $classGroupId,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'status' => 'draft',
        ]));
    }
}
