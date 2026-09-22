<?php

namespace App\Http\Requests;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreGuardianLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        $learner = $this->route('learner');

        return $school instanceof School
            && $learner instanceof Enrolment
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [GuardianLink::class, $school, $learner]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(fn ($query) => $query->whereNotNull('email_verified_at')),
            ],
            'relationship' => ['required', 'string', 'max:50'],
        ];
    }
}
