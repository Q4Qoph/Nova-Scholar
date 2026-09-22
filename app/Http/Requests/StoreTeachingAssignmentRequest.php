<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\TeachingAssignment;
use App\SchoolRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTeachingAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [TeachingAssignment::class, $school]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'class_group_id' => [
                'required',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $school instanceof School ? $school->id : 0)),
                Rule::unique('teaching_assignments', 'class_group_id')->where(
                    fn ($query) => $query
                        ->where('subject_id', $this->input('subject_id'))
                        ->where('teacher_user_id', $this->input('teacher_user_id'))
                ),
            ],
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('school_id', $school instanceof School ? $school->id : 0)),
            ],
            'teacher_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $school = $this->route('school');
            if (! $school instanceof School || ! $this->input('teacher_user_id')) {
                return;
            }

            $isTeacher = SchoolMembership::query()
                ->active()
                ->where('school_id', $school->id)
                ->where('user_id', $this->input('teacher_user_id'))
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::Teacher->value))
                ->exists();
            if (! $isTeacher) {
                $validator->errors()->add('teacher_user_id', 'The selected user is not an active teacher in this school.');
            }
        });
    }
}
