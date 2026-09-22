<?php

namespace App\Http\Requests;

use App\Models\Enrolment;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TransferLearnerRequest extends FormRequest
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
            'destination_school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'admission_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('enrolments', 'admission_number')->where(fn ($query) => $query->where('school_id', $this->integer('destination_school_id'))),
            ],
            'transferred_on' => ['required', 'date'],
        ];
    }
}
