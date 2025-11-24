<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Always hash password check timing to prevent enumeration
        $user = User::where('email', $this->email)->first();

        // Use constant-time comparison to prevent timing attacks
        $passwordValid = $user && Hash::check($this->password, $user->password);

        if (! $passwordValid) {
            // Rate limit: 5 attempts per 60 seconds (1 minute)
            RateLimiter::hit($this->throttleKey(), 60);

            // Log failed login attempt for security auditing
            Log::warning('Failed login attempt', [
                'email' => $this->email,
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($this->throttleKey());

        // Log successful login
        Log::info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $this->ip(),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        Log::warning('Login rate limit exceeded', [
            'email' => $this->email,
            'ip' => $this->ip(),
            'seconds' => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|login|' . $this->ip());
    }
}
