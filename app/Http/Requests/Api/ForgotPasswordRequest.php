<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only root_user can send password reset links.
     * Note: This is a secondary check - the root_user middleware also enforces this.
     */
    public function authorize(): bool
    {
        // The root_user middleware already ensures only root_user can access this endpoint
        // This is kept as a defense-in-depth measure
        return $this->user()?->role === 'root_user' ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required_without:email', 'integer', 'exists:users,id'],
            'email' => ['required_without:user_id', 'string', 'email', 'exists:users,email'],
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
            'user_id.required_without' => 'Either user_id or email must be provided.',
            'user_id.exists' => 'The specified user does not exist.',
            'email.required_without' => 'Either user_id or email must be provided.',
            'email.exists' => 'The specified email does not exist in the system.',
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
    }
}

