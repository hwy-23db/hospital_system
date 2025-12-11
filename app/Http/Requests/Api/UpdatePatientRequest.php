<?php

namespace App\Http\Requests\Api;

use App\Models\Patient;
use App\Rules\MyanmarAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $patientId = $this->route('id');

        return [
            // Basic identification
            'name' => 'sometimes|string|max:255',
            'nrc_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('patients', 'nrc_number')->ignore($patientId),
            ],
            'sex' => 'sometimes|nullable|string|in:male,female,other',
            'age' => 'sometimes|nullable|integer|min:0|max:150',
            'dob' => 'sometimes|nullable|date|before_or_equal:today',
            'contact_phone' => 'sometimes|nullable|string|max:20',

            // Address - accepts JSON string with {region, district, township} or plain text
            'permanent_address' => ['sometimes', 'nullable', new MyanmarAddress()],

            // Personal details
            'marital_status' => 'sometimes|nullable|string|in:single,married,divorced,widowed,other',
            'ethnic_group' => 'sometimes|nullable|string|max:100',
            'religion' => 'sometimes|nullable|string|max:100',
            'occupation' => 'sometimes|nullable|string|max:100',
            'father_name' => 'sometimes|nullable|string|max:255',
            'mother_name' => 'sometimes|nullable|string|max:255',

            // Emergency contact
            'nearest_relative_name' => 'sometimes|nullable|string|max:255',
            'nearest_relative_phone' => 'sometimes|nullable|string|max:20',
            'relationship' => 'sometimes|nullable|string|max:50',

            // Medical info (permanent)
            'blood_type' => ['sometimes', 'nullable', Rule::in(Patient::bloodTypes())],
            'known_allergies' => 'sometimes|nullable|string|max:500',
            'chronic_conditions' => 'sometimes|nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nrc_number.unique' => 'This NRC number is already registered to another patient.',
            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
            'sex.in' => 'Sex must be male, female, or other.',
            'marital_status.in' => 'Invalid marital status.',
            'blood_type.in' => 'Invalid blood type.',
        ];
    }
}
