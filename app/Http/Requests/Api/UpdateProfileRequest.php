<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Users can only update their own profile.
     */
    public function authorize(): bool
    {
        // Users can only update their own profile
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $userId = $user ? $user->id : null;

        return [
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $userId, // Ignore current user's email
            ],
            'password' => [
                'sometimes',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => ['required_with:password', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name field may only contain letters and spaces.',
            'email.unique' => 'The email has already been taken.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password_confirmation.required_with' => 'The password confirmation is required when password is provided.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase if provided
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        // Trim and sanitize name if provided
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }
}



