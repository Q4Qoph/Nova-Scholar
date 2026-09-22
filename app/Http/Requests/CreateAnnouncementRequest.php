<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [Announcement::class, $school]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = $school instanceof School ? $school->id : 0;

        return [
            'audience_type' => ['required', Rule::in(['all_guardians', 'class_guardians'])],
            'class_group_id' => [
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', 'active')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('audience_type') === 'class_guardians' && ! $this->filled('class_group_id')) {
                $validator->errors()->add('class_group_id', 'Select a class for a class notice.');
            }

            if ($this->input('audience_type') === 'all_guardians' && $this->filled('class_group_id')) {
                $validator->errors()->add('class_group_id', 'A school-wide notice cannot include a class.');
            }
        });
    }
}
