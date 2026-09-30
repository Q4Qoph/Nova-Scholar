<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FeeStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $toRules = ['nullable', 'date_format:Y-m-d'];

        if ($this->filled('from')) {
            $toRules[] = 'after_or_equal:from';
        }

        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => $toRules,
        ];
    }
}
