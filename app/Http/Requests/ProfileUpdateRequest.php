<?php

namespace App\Http\Requests;

use App\Models\User;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Normalize the comma-separated subject field before validation.
     */
    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('preferred_subjects'))) {
            return;
        }

        $this->merge([
            'preferred_subjects' => array_values(array_filter(array_map(
                fn (string $subject): string => trim($subject),
                explode(',', $this->input('preferred_subjects')),
            ))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'preferred_subjects' => ['nullable', 'array', 'max:10'],
            'preferred_subjects.*' => ['string', 'max:60'],
            'daily_study_goal_minutes' => ['nullable', 'integer', 'between:0,720'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }
}
