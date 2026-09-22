<?php

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use App\Models\School;
use App\SchoolRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [AttendanceSession::class, $school]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = $school instanceof School ? $school->id : 0;

        return [
            'attendance_session_id' => [
                'nullable',
                'integer',
                Rule::exists('attendance_sessions', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'version' => ['nullable', 'integer', 'min:0'],
            'class_group_id' => [
                'required',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', 'active')),
            ],
            'teaching_assignment_id' => [
                'required',
                'integer',
                Rule::exists('teaching_assignments', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', 'active')),
            ],
            'session_date' => ['required', 'date'],
            'entries' => ['nullable', 'array'],
            'entries.*.enrolment_id' => ['required', 'integer'],
            'entries.*.status' => ['required', Rule::in(['unmarked', 'present', 'absent', 'late', 'excused'])],
            'entries.*.correction_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $school = $this->route('school');
            if (! $school instanceof School || ! $this->input('teaching_assignment_id')) {
                return;
            }

            $assignment = $school->teachingAssignments()
                ->whereKey($this->integer('teaching_assignment_id'))
                ->where('class_group_id', $this->integer('class_group_id'))
                ->where('status', 'active')
                ->first();
            if ($assignment === null) {
                $validator->errors()->add('teaching_assignment_id', 'The assignment must belong to the selected class and school.');

                return;
            }

            $isAdmin = $this->user()?->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
            if (! $isAdmin && $assignment->teacher_user_id !== $this->user()?->id) {
                $validator->errors()->add('teaching_assignment_id', 'You may only record attendance for your assigned class.');
            }

            if ($this->filled('attendance_session_id') && $this->input('version') === null) {
                $validator->errors()->add('version', 'The attendance version is required when updating a register.');
            }
        });
    }
}
