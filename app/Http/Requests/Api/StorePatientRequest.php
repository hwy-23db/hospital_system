<?php

namespace App\Http\Requests\Api;

use App\Models\Patient;
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
            'name' => 'required|string|max:255',
            
            // Basic identification
            'nrc_number' => 'nullable|string|max:50|unique:patients,nrc_number',
            'sex' => 'nullable|string|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'dob' => 'nullable|date|before_or_equal:today',
            'contact_phone' => 'nullable|string|max:20',
            
            // Address
            'permanent_address' => 'nullable|string|max:500',
            
            // Personal details
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed,other',
            'ethnic_group' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            
            // Emergency contact
            'nearest_relative_name' => 'nullable|string|max:255',
            'nearest_relative_phone' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            
            // Medical info (permanent)
            'blood_type' => ['nullable', Rule::in(Patient::bloodTypes())],
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
            'nrc_number.unique' => 'This NRC number is already registered.',
            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
            'sex.in' => 'Sex must be male, female, or other.',
            'marital_status.in' => 'Invalid marital status.',
            'blood_type.in' => 'Invalid blood type.',
        ];
    }
}
