<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School && $this->user()?->can('create', [FeeSchedule::class, $school]) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', Rule::in(['cash', 'bank', 'mpesa'])],
            'source_reference' => ['nullable', 'required_if:source,bank,mpesa', 'string', 'max:120'],
            'submission_key' => ['required', 'uuid'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'verification_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $source = $this->input('source');

        $this->merge([
            'source' => is_string($source) ? strtolower(trim($source)) : $source,
            'source_reference' => is_string($this->input('source_reference'))
                ? strtoupper(trim($this->input('source_reference')))
                : $this->input('source_reference'),
            'currency' => is_string($this->input('currency')) ? strtoupper(trim($this->input('currency'))) : $this->input('currency'),
            'verification_note' => $this->input('verification_note') ?: null,
        ]);
    }
}
