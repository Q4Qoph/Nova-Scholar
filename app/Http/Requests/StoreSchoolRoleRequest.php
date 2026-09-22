<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Models\SchoolMembership;
use App\SchoolRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->route('school');
        if (! $this->user() || ! $school instanceof School) {
            return false;
        }

        return SchoolMembership::query()
            ->active()
            ->where('school_id', $school->id)
            ->where('user_id', $this->user()->id)
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['role' => ['required', Rule::in([
            SchoolRole::SchoolAdmin->value,
            SchoolRole::Teacher->value,
            SchoolRole::Bursar->value,
        ])]];
    }
}
