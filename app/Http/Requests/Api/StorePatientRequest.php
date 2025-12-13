<?php

namespace App\Http\Requests\Api;

use App\Models\Patient;
use App\Rules\MyanmarAddress;
use App\Rules\MyanmarNrc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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
     * Only validates permanent demographic data.
     */
    public function rules(): array
    {
        return [
            // Required fields
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],

            // Basic identification
            'nrc_number' => ['required', new MyanmarNrc(), 'unique:patients,nrc_number'],
            'sex' => 'required|string|in:male,female,other',
            'age' => 'required|integer|min:0|max:150',
            'dob' => 'required|date|before_or_equal:today',
            'contact_phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],

            // Address - accepts JSON string with {region, district, township} or plain text
            'permanent_address' => ['required', new MyanmarAddress()],

            // Personal details
            'marital_status' => 'required|string|in:single,married,divorced,widowed,other',
            'ethnic_group' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'occupation' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'father_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'mother_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],

            // Emergency contact
            'nearest_relative_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'nearest_relative_phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'relationship' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'],

            // Medical info (permanent)
            'blood_type' => ['required', Rule::in(Patient::bloodTypes())],
            'known_allergies' => 'nullable|string|max:500',
            'chronic_conditions' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Patient name is required.',
            'name.regex' => 'Patient name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nrc_number.unique' => 'This NRC number is already registered.',
            'sex.required' => 'Patient sex is required.',
            'sex.in' => 'Sex must be male, female, or other.',
            'age.required' => 'Patient age is required.',
            'age.integer' => 'Age must be a number.',
            'age.min' => 'Age cannot be negative.',
            'age.max' => 'Age cannot exceed 150 years.',
            'dob.required' => 'Date of birth is required.',
            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
            'contact_phone.required' => 'Contact phone number is required.',
            'contact_phone.regex' => 'Contact phone can only contain digits, spaces, plus signs, hyphens, and parentheses.',
            'permanent_address.required' => 'Permanent address is required.',
            'marital_status.required' => 'Marital status is required.',
            'marital_status.in' => 'Invalid marital status.',
            'occupation.required' => 'Occupation is required.',
            'occupation.regex' => 'Occupation can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'father_name.required' => 'Father name is required.',
            'father_name.regex' => 'Father name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'mother_name.required' => 'Mother name is required.',
            'mother_name.regex' => 'Mother name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nearest_relative_name.required' => 'Nearest relative name is required.',
            'nearest_relative_name.regex' => 'Nearest relative name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'nearest_relative_phone.required' => 'Nearest relative phone number is required.',
            'nearest_relative_phone.regex' => 'Nearest relative phone can only contain digits, spaces, plus signs, hyphens, and parentheses.',
            'relationship.required' => 'Relationship to nearest relative is required.',
            'relationship.regex' => 'Relationship can only contain letters, spaces, hyphens, and apostrophes.',
            'blood_type.required' => 'Blood type is required.',
            'blood_type.in' => 'Invalid blood type.',
        ];
    }
}
