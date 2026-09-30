<x-filament-panels::page>
    <x-draft-guard class="space-y-6">
        <nav class="flex flex-wrap gap-3" aria-label="Learning tasks">
            <a class="rounded-lg border px-4 py-2 text-primary-600" href="{{ $this->taskUrl('courses') }}">Courses</a>
            @if ($courseId !== null)
                @foreach (['lessons' => 'Lessons', 'assignments' => 'Assignments', 'review' => 'Review'] as $mode => $label)
                    <a class="rounded-lg border px-4 py-2 {{ $taskView === $mode ? 'bg-primary-50 text-primary-700 dark:bg-primary-950' : '' }}" aria-current="{{ $taskView === $mode ? 'page' : 'false' }}" href="{{ $this->taskUrl($mode) }}">{{ $label }}</a>
                @endforeach
            @endif
        </nav>
        @if ($taskView === 'courses')
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Create a course</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Each course belongs to one active teaching assignment.</p>
            </div>

            <form class="mt-5 grid gap-4 md:grid-cols-3" wire:submit="createCourse">
                <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Teaching assignment</span>
                    <select class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="teachingAssignmentId">
                        <option value="">Select an assignment</option>
                        @foreach ($this->getTeachingAssignments() as $assignment)
                            <option value="{{ $assignment->id }}">{{ $assignment->classGroup->name }} · {{ $assignment->subject->name }} · {{ $assignment->teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teachingAssignmentId') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Course title</span>
                    <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="255" wire:model="courseTitle" type="text" autocomplete="off">
                    @error('courseTitle') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end">
                    <x-filament::button type="submit">Create course</x-filament::button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Your courses</h2>
            @php($resourceUsage = $this->getResourceUsage())
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Private storage: {{ number_format($resourceUsage['count']) }} / 500 resources · {{ number_format($resourceUsage['bytes'] / 1024 / 1024, 1) }} / 2048 MB
            </p>
            @if ($resourceUsage['count_percent'] >= 80 || $resourceUsage['bytes_percent'] >= 80)
                <p class="mt-2 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-950 dark:text-warning-200" role="status">
                    Private resource storage is at least 80% used. Purged bytes no longer count toward the limit.
                </p>
            @endif
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($this->getCourses() as $course)
                    <a class="rounded-lg border p-4 text-left transition hover:border-primary-500 {{ $courseId === $course->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}" href="{{ $this->taskUrl('lessons', $course->id) }}">
                        <span class="block font-semibold text-gray-950 dark:text-white">{{ $course->title }}</span>
                        <span class="mt-1 block text-sm text-gray-600 dark:text-gray-300">{{ $course->teachingAssignment->classGroup->name }} · {{ $course->teachingAssignment->subject->name }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">No course has been created for your active teaching assignments.</p>
                @endforelse
            </div>
        </section>

        @endif
        @if ($this->getSelectedCourse() instanceof \App\Models\SchoolCourse)
            @php($selectedCourse = $this->getSelectedCourse())
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $selectedCourse->title }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $selectedCourse->teachingAssignment->classGroup->name }} · {{ $selectedCourse->teachingAssignment->subject->name }}</p>
                    </div>
                    @if ($taskView === 'lessons')
                    <x-filament::button color="gray" wire:click="newDraft" type="button">New lesson</x-filament::button>
                    @endif
                </div>

                @if ($taskView === 'lessons')
                <form class="mt-5 space-y-4" wire:submit="saveDraft">
                    <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <span>Lesson title</span>
                        <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="255" wire:model="lessonTitle" type="text">
                        @error('lessonTitle') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <span>Lesson text</span>
                        <textarea class="min-h-48 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="50000" wire:model="lessonBody"></textarea>
                        @error('lessonBody') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="flex flex-wrap gap-3">
                        <x-filament::button type="submit">Save draft</x-filament::button>
                        <x-filament::button color="gray" wire:click="previewDraft" type="button">Preview draft</x-filament::button>
                    </div>
                </form>

                @if ($previewing)
                    <article class="mt-5 rounded-lg border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-900 dark:bg-indigo-950">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Learner preview · not published</p>
                        <h3 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">{{ $lessonTitle }}</h3>
                        <div class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-gray-800 dark:text-gray-200">{{ $lessonBody }}</div>
                    </article>
                @endif

                @if ($versionId !== null)
                    <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-950 dark:text-white">Attach a private resource</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Learners cannot open this file until it passes validation and malware scanning.</p>
                        <form class="mt-4 grid gap-4 md:grid-cols-2" wire:submit="uploadResource">
                            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200 md:col-span-2">
                                <span>PDF, DOCX, TXT, JPEG, or PNG · up to 10 MB</span>
                                <input class="block w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="upload" type="file" accept=".pdf,.docx,.txt,.jpg,.jpeg,.png">
                                @error('upload') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <span>Rights basis</span>
                                <select class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="rightsBasis">
                                    <option value="educator_created">Created by educator</option>
                                    <option value="school_owned">Owned by school</option>
                                    <option value="licensed">Licensed for school use</option>
                                    <option value="permission_granted">Permission granted</option>
                                </select>
                                @error('rightsBasis') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <span>Licence or permission source</span>
                                <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="512" wire:model="rightsReference" type="text">
                                @error('rightsReference') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200 md:col-span-2">
                                <input class="mt-1 rounded border-gray-300" wire:model="rightsAttested" type="checkbox">
                                <span>I have the right to share this material with learners enrolled in this school.</span>
                            </label>
                            @error('rightsAttested') <span class="text-xs text-danger-600 md:col-span-2">{{ $message }}</span> @enderror
                            <div class="md:col-span-2">
                                <x-filament::button type="submit">Upload to quarantine</x-filament::button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="mt-7 space-y-4">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Lessons and versions</h3>
                    @forelse ($selectedCourse->lessons as $lesson)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Lesson {{ $lesson->position }}</p>
                            <div class="mt-2 space-y-3">
                                @forelse ($lesson->versions->sortByDesc('version_number') as $version)
                                    <div class="flex flex-col justify-between gap-3 border-t border-gray-100 pt-3 dark:border-gray-800 sm:flex-row sm:items-start">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-950 dark:text-white">{{ $version->title }} · v{{ $version->version_number }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ ucfirst($version->status->value) }}</p>
                                            @if ($version->resources->isNotEmpty())
                                                <ul class="mt-2 space-y-1 text-sm">
                                                    @foreach ($version->resources as $resource)
                                                        <li class="flex flex-wrap items-center gap-2">
                                                            <span class="text-gray-700 dark:text-gray-200">{{ $resource->display_name }}</span>
                                                            <span class="text-xs text-gray-500">{{ ucfirst($resource->status->value) }}</span>
                                                            @if ($resource->status === \App\Models\SchoolLessonResourceStatus::ScanPending && $resource->scan_result_code !== null)
                                                                <button class="text-xs font-medium text-primary-700 underline dark:text-primary-300" wire:click="retryResourceScan({{ $resource->id }})" type="button">Retry scan</button>
                                                            @endif
                                                            @if ($resource->status === \App\Models\SchoolLessonResourceStatus::Clean && $resource->storage_key !== null)
                                                                <a class="text-xs font-medium text-primary-700 underline dark:text-primary-300" href="{{ route('schools.learning.resources.download', ['school' => $getSchool(), 'lessonResource' => $resource]) }}">Download</a>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 flex-wrap gap-2">
                                            @if ($version->status === \App\Models\SchoolLessonVersionStatus::Draft)
                                                <x-filament::button size="sm" color="gray" wire:click="openDraft({{ $version->id }})" type="button">Edit draft</x-filament::button>
                                                <x-filament::button size="sm" wire:click="publishVersion({{ $version->id }})" type="button">Publish</x-filament::button>
                                            @elseif ($version->status === \App\Models\SchoolLessonVersionStatus::Published)
                                                <x-filament::button size="sm" color="gray" wire:click="startNewVersion({{ $lesson->id }})" type="button">Create new version</x-filament::button>
                                                <x-filament::button size="sm" color="danger" wire:click="withdrawVersion({{ $version->id }})" wire:confirm="Withdraw this lesson? Learners will lose access immediately." type="button">Withdraw</x-filament::button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="border-t border-gray-100 pt-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">No versions yet.</p>
                                @endforelse
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-300">Save a lesson draft to begin.</p>
                    @endforelse
                </div>

                @endif
                @if (in_array($taskView, ['assignments', 'review'], true))
                <section class="mt-8 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div>
                        <h3 class="font-semibold text-gray-950 dark:text-white">{{ $taskView === 'review' ? 'Review responses' : 'Assignments' }}</h3>
                        @if ($taskView === 'assignments')
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Publish text assignments from a published lesson. The class roster is frozen when you publish.</p>
                        @endif
                    </div>
                    @if ($taskView === 'assignments')
                    <form class="grid gap-4 md:grid-cols-2" wire:submit="saveAssignmentDraft">
                        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <span>Published lesson</span>
                            <select class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="assignmentLessonVersionId">
                                <option value="">Select a lesson</option>
                                @foreach ($selectedCourse->lessons as $lesson)
                                    @foreach ($lesson->versions as $version)
                                        @if ($version->status === \App\Models\SchoolLessonVersionStatus::Published)
                                            <option value="{{ $version->id }}">Lesson {{ $lesson->position }} · {{ $version->title }}</option>
                                        @endif
                                    @endforeach
                                @endforeach
                            </select>
                            @error('assignmentLessonVersionId') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <span>Assignment title</span>
                            <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="255" wire:model="assignmentTitle" type="text">
                            @error('assignmentTitle') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200 md:col-span-2">
                            <span>Instructions</span>
                            <textarea class="min-h-28 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" maxlength="10000" wire:model="assignmentInstructions"></textarea>
                            @error('assignmentInstructions') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <span>Due date and time</span>
                            <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="assignmentDueAt" type="datetime-local">
                            @error('assignmentDueAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <span>Late submission cutoff (optional)</span>
                            <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" wire:model="assignmentCutoffAt" type="datetime-local">
                            @error('assignmentCutoffAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <div class="flex flex-wrap items-center gap-3 md:col-span-2">
                            <x-filament::button type="submit">{{ $learningAssignmentId === null ? 'Save assignment draft' : 'Update assignment draft' }}</x-filament::button>
                            @if ($learningAssignmentId !== null)
                                <x-filament::button color="gray" wire:click="newAssignmentDraft" type="button">New assignment</x-filament::button>
                            @endif
                        </div>
                    </form>
                    @endif
                    @if ($taskView === 'assignments' || $reviewAssignmentId === null)
                    <div class="grid gap-3 md:grid-cols-2">
                        @forelse ($this->getLearningAssignments() as $learningAssignment)
                            <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-gray-950 dark:text-white">{{ $learningAssignment->title }}</h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $learningAssignment->sourceLessonVersion->title }} · Due {{ $learningAssignment->due_at->timezone($this->getSchool()->timezone)->format('d M Y, H:i') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ ucfirst($learningAssignment->status) }} · {{ $learningAssignment->recipients_count }} learners assigned · {{ $learningAssignment->submitted_count }} submitted</p>
                                    </div>
                                    @if ($learningAssignment->status === \App\Models\SchoolLearningAssignmentStatus::Draft->value)
                                        <div class="flex gap-2">
                                            <x-filament::button size="sm" color="gray" wire:click="editAssignmentDraft({{ $learningAssignment->id }})" type="button">Edit</x-filament::button>
                                            <x-filament::button size="sm" wire:click="publishLearningAssignment({{ $learningAssignment->id }})" wire:confirm="Publish this assignment to the current class roster?" type="button">Publish</x-filament::button>
                                        </div>
                                    @elseif ($learningAssignment->status === 'published')
                                        <x-filament::button size="sm" color="gray" tag="a" href="{{ $this->taskUrl('review', $courseId, $learningAssignment->id) }}">Review submissions</x-filament::button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-gray-600 dark:text-gray-300">No assignments have been drafted for this course.</p>
                        @endforelse
                    </div>
                    @endif
                    @if ($taskView === 'review' && ($reviewAssignment = $this->getReviewAssignment()))
                        <section class="mt-6 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700" aria-label="Submission reviews">
                            <h3 class="font-semibold text-gray-950 dark:text-white">Submissions · {{ $reviewAssignment->title }}</h3>
                            <a class="text-sm text-primary-600 hover:underline" href="{{ $this->taskUrl('assignments') }}">← Back to assignments</a>
                            @if ($reviewSubmissionId === null)
                            @php($reviewSubmissions = $this->getReviewSubmissions())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @forelse ($reviewSubmissions as $submittedWork)
                                    <article class="min-w-0 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="review-submission-{{ $submittedWork->id }}">
                                        <h4 class="break-words font-medium">{{ $submittedWork->learnerProfile->first_name }} {{ $submittedWork->learnerProfile->last_name }}</h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $submittedWork->is_late ? 'Submitted late' : 'Submitted' }} · {{ $submittedWork->submitted_at->timezone($this->getSchool()->timezone)->format('d M Y, H:i') }}</p>
                                        <p class="mt-1 text-sm">{{ $submittedWork->review === null ? 'Awaiting review' : ($submittedWork->review->released_at === null ? 'Feedback draft · private' : 'Feedback released') }}</p>
                                        <x-filament::button class="mt-3" size="sm" color="gray" tag="a" href="{{ $this->taskUrl('review', $courseId, $reviewAssignmentId, $submittedWork->id) }}">Open response</x-filament::button>
                                    </article>
                                @empty
                                    <p class="text-sm text-gray-600 dark:text-gray-300">No final responses have been submitted yet.</p>
                                @endforelse
                            </div>
                            {{ $reviewSubmissions->links() }}
                            @endif
                            @if ($reviewedSubmission = $this->getReviewSubmission())
                                <a class="text-sm text-primary-600 hover:underline" href="{{ $this->taskUrl('review', $courseId, $reviewAssignmentId) }}">← Back to responses</a>
                                <article class="min-w-0 rounded-lg bg-gray-50 p-4 dark:bg-gray-800" wire:key="review-editor-{{ $reviewedSubmission->id }}">
                                    <h4 class="font-semibold">Response · {{ $reviewedSubmission->learnerProfile->first_name }} {{ $reviewedSubmission->learnerProfile->last_name }}</h4>
                                    <div class="mt-3 whitespace-pre-wrap break-words text-sm leading-7">{{ $reviewedSubmission->response_text }}</div>
                                    @if ($reviewedSubmission->review?->released_at !== null)
                                        <p class="mt-4 text-sm font-medium text-success-700 dark:text-success-400" role="status">Feedback released · {{ $reviewedSubmission->review->released_at->timezone($this->getSchool()->timezone)->format('d M Y, H:i') }}</p>
                                        <div class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $reviewedSubmission->review->feedback }}</div>
                                        @if ($reviewedSubmission->review->score !== null)
                                            <p class="mt-2 text-sm font-semibold">Score: {{ $reviewedSubmission->review->score }} / {{ $reviewedSubmission->review->maximum_score }}</p>
                                        @else
                                            <p class="mt-2 text-sm">Feedback only · no score</p>
                                        @endif
                                    @else
                                        <p class="mt-3 text-sm" role="status">{{ $reviewedSubmission->review === null ? 'Awaiting review' : 'Feedback draft · private' }}</p>
                                        <form class="mt-4 grid gap-4" wire:submit="saveSubmissionReview">
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Save your feedback first, then release it to the learner. Released feedback cannot be edited.</p>
                                            <label class="grid gap-1 text-sm font-medium">
                                                <span>Feedback</span>
                                                <textarea class="min-h-32 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" wire:model="reviewFeedback" maxlength="10000" required></textarea>
                                                @error('reviewFeedback') <span class="text-sm text-danger-600" role="alert">{{ $message }}</span> @enderror
                                            </label>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <label class="grid gap-1 text-sm font-medium">
                                                    <span>Score (optional)</span>
                                                    <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" wire:model="reviewScore" type="number" min="0" max="1000000" step="1">
                                                    @error('reviewScore') <span class="text-sm text-danger-600" role="alert">{{ $message }}</span> @enderror
                                                </label>
                                                <label class="grid gap-1 text-sm font-medium">
                                                    <span>Maximum score (required with a score)</span>
                                                    <input class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" wire:model="reviewMaximumScore" type="number" min="1" max="1000000" step="1">
                                                    @error('reviewMaximumScore') <span class="text-sm text-danger-600" role="alert">{{ $message }}</span> @enderror
                                                </label>
                                            </div>
                                            <div class="flex flex-wrap gap-3">
                                                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="saveSubmissionReview,releaseSubmissionReview">Save feedback draft</x-filament::button>
                                                @if ($reviewedSubmission->review !== null)
                                                    <x-filament::button color="success" type="button" wire:click="releaseSubmissionReview" wire:confirm="Release the saved feedback? The learner will see it and it cannot be edited." wire:loading.attr="disabled" wire:target="saveSubmissionReview,releaseSubmissionReview">Release feedback</x-filament::button>
                                                @endif
                                            </div>
                                        </form>
                                    @endif
                                </article>
                            @endif
                        </section>
                    @endif
                </section>
                @endif
            </section>
        @endif
    </x-draft-guard>
</x-filament-panels::page>
