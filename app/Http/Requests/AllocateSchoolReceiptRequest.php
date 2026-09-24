<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocateSchoolReceiptRequest extends FormRequest
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
            'school_receipt_id' => [
                'required',
                'integer',
                Rule::exists('school_receipts', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('school_id', $this->route('school')->id)),
            ],
            'fee_charge_id' => [
                'required',
                'integer',
                Rule::exists('fee_charges', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('school_id', $this->route('school')->id)
                    ->where('status', 'posted')),
            ],
            'allocation_key' => ['required', 'uuid'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
        ];
    }
}
