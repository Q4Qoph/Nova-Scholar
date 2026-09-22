<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearnerLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'learner_login_id' => ['required', 'string', 'size:12'],
            'password' => ['required', 'string'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();
        $user = User::query()
            ->where('account_type', 'managed_learner')
            ->where('learner_login_id', $this->string('learner_login_id')->toString())
            ->whereNotNull('learner_activated_at')
            ->whereNull('learner_deactivated_at')
            ->first();

        if (! $user instanceof User || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['learner_login_id' => trans('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages(['learner_login_id' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]);
    }

    private function throttleKey(): string
    {
        return Str::upper($this->string('learner_login_id')->toString()).'|'.$this->ip();
    }
}
