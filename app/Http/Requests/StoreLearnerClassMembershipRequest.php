<?php

namespace App\Http\Requests;

use App\Models\LearnerClassMembership;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLearnerClassMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            && $this->user() !== null
            && Gate::forUser($this->user())->allows('create', [LearnerClassMembership::class, $school]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'class_group_id' => [
                'required',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $school instanceof School ? $school->id : 0)->where('status', 'active')),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
