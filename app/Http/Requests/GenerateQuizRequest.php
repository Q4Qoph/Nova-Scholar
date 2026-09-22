<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:multiple_choice,short_answer,true_false'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'question_count' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
