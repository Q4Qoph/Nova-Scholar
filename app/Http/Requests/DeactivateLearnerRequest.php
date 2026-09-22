<?php

namespace App\Http\Requests;

use App\Models\Enrolment;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeactivateLearnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [Enrolment::class, $school]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'deactivated_on' => ['required', 'date'],
        ];
    }
}
