<?php

namespace App\Http\Requests;

use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PostFeeChargeBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && Gate::forUser($this->user())->allows('create', [FeeSchedule::class, $school]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'fee_schedule_id' => ['required', 'integer', Rule::exists('fee_schedules', 'id')->where(fn ($query) => $query->where('school_id', $school instanceof School ? $school->id : 0)->where('status', 'active'))],
            'batch_key' => ['required', 'string', 'max:100', 'alpha_dash'],
        ];
    }
}
