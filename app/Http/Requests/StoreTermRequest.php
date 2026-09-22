<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $academicYear = $this->route('academicYear');

        return $academicYear instanceof AcademicYear
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [Term::class, $academicYear]);
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
                'max:50',
                Rule::unique('terms', 'name')->where(fn ($query) => $query->where('academic_year_id', $this->route('academicYear')?->id)),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $academicYear = $this->route('academicYear');
            if (! $academicYear instanceof AcademicYear || ! $this->input('starts_on') || ! $this->input('ends_on')) {
                return;
            }

            $overlaps = Term::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('starts_on', '<=', $this->input('ends_on'))
                ->where('ends_on', '>=', $this->input('starts_on'))
                ->exists();
            if ($overlaps) {
                $validator->errors()->add('starts_on', 'The term dates overlap an existing term.');
            }
        });
    }
}
