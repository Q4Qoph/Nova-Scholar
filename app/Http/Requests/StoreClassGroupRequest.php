<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreClassGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $academicYear = $this->route('academicYear');

        return $academicYear instanceof AcademicYear
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [ClassGroup::class, $academicYear]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('class_groups', 'name')->where(fn ($query) => $query->where('academic_year_id', $this->route('academicYear')?->id)),
            ],
            'grade_level' => ['required', 'string', 'max:50'],
            'stream' => ['nullable', 'string', 'max:50'],
        ];
    }
}
