<?php

namespace App\Http\Requests;

use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateFeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && Gate::forUser($this->user())->allows('create', [FeeSchedule::class, $school]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = $school instanceof School ? $school->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'term_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', 'open'))],
            'class_group_id' => ['nullable', 'integer', Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', 'active'))],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $school = $this->route('school');
            if (! $school instanceof School) {
                return;
            }

            $term = $this->input('term_id') ? $school->terms()->find($this->integer('term_id')) : null;
            $classGroup = $this->input('class_group_id') ? $school->classGroups()->find($this->integer('class_group_id')) : null;
            if ($term !== null && $classGroup !== null && $classGroup->academic_year_id !== $term->academic_year_id) {
                $validator->errors()->add('class_group_id', 'The class must belong to the selected term academic year.');
            }
        });
    }
}
