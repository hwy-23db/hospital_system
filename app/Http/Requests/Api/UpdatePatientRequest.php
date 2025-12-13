<?php

namespace App\Http\Requests\Api;

use App\Models\Patient;
use App\Rules\MyanmarAddress;
use App\Rules\MyanmarNrc;
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
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'nrc_number' => [
                'sometimes',
                'required',
                new MyanmarNrc(),
                Rule::unique('patients', 'nrc_number')->ignore($patientId),
            ],
            'sex' => 'sometimes|required|string|in:male,female,other',
            'age' => 'sometimes|required|integer|min:0|max:150',
            'dob' => 'sometimes|required|date|before_or_equal:today',
            'contact_phone' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],

            // Address - accepts JSON string with {region, district, township} or plain text
            'permanent_address' => ['sometimes', 'required', new MyanmarAddress()],

            // Personal details
            'marital_status' => 'sometimes|required|string|in:single,married,divorced,widowed,other',
            'ethnic_group' => 'sometimes|nullable|string|max:100',
            'religion' => 'sometimes|nullable|string|max:100',
            'occupation' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'father_name' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'mother_name' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],

            // Emergency contact
            'nearest_relative_name' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'nearest_relative_phone' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'relationship' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'],

            // Medical info (permanent)
            'blood_type' => ['sometimes', 'required', Rule::in(Patient::bloodTypes())],
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
            'name.regex' => 'Patient name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nrc_number.unique' => 'This NRC number is already registered to another patient.',
            'sex.required' => 'Patient sex cannot be null when updating.',
            'sex.in' => 'Sex must be male, female, or other.',
            'age.required' => 'Patient age cannot be null when updating.',
            'age.integer' => 'Age must be a number.',
            'age.min' => 'Age cannot be negative.',
            'age.max' => 'Age cannot exceed 150 years.',
            'dob.required' => 'Date of birth cannot be null when updating.',
            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
            'contact_phone.required' => 'Contact phone number cannot be null when updating.',
            'contact_phone.regex' => 'Contact phone can only contain digits, spaces, plus signs, hyphens, and parentheses.',
            'permanent_address.required' => 'Permanent address cannot be null when updating.',
            'marital_status.required' => 'Marital status cannot be null when updating.',
            'marital_status.in' => 'Invalid marital status.',
            'occupation.required' => 'Occupation cannot be null when updating.',
            'occupation.regex' => 'Occupation can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'father_name.required' => 'Father name cannot be null when updating.',
            'father_name.regex' => 'Father name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'mother_name.required' => 'Mother name cannot be null when updating.',
            'mother_name.regex' => 'Mother name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nearest_relative_name.required' => 'Nearest relative name cannot be null when updating.',
            'nearest_relative_name.regex' => 'Nearest relative name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nearest_relative_phone.required' => 'Nearest relative phone number cannot be null when updating.',
            'nearest_relative_phone.regex' => 'Nearest relative phone can only contain digits, spaces, plus signs, hyphens, and parentheses.',
            'relationship.required' => 'Relationship to nearest relative cannot be null when updating.',
            'relationship.regex' => 'Relationship can only contain letters, spaces, hyphens, and apostrophes.',
            'blood_type.required' => 'Blood type cannot be null when updating.',
            'blood_type.in' => 'Invalid blood type.',
        ];
    }
}
