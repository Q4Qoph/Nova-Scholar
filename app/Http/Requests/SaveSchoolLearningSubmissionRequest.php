<?php

namespace App\Http\Requests;

use App\Models\SchoolLearningAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveSchoolLearningSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $this->user() !== null
            && $assignment instanceof SchoolLearningAssignment
            && $this->user()->can('viewForLearner', $assignment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'response_text' => ['required', 'string', 'max:10000'],
        ];
    }
}
