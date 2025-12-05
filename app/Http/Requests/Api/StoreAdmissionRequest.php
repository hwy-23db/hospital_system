<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
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
        return [
            // Admission type
            'admission_type' => 'nullable|in:outpatient,inpatient',
            
            // Staff assignment
            'doctor_id' => 'nullable|exists:users,id',
            'nurse_id' => 'nullable|exists:users,id',
            
            // Required admission details
            'admission_date' => 'required|date|before_or_equal:today',
            'admitted_for' => 'required|string|max:500',
            
            // Optional admission details
            'admission_time' => 'nullable|date_format:H:i',
            'present_address' => 'nullable|string|max:500',
            'referred_by' => 'nullable|string|max:255',
            'police_case' => 'nullable|string|in:yes,no',
            'service' => 'nullable|string|max:255',
            // Ward: Required for inpatient, prohibited for outpatient
            'ward' => [
                'nullable',
                'required_if:admission_type,inpatient',
                'prohibited_if:admission_type,outpatient',
                'string',
                'max:100'
            ],
            // Bed number: Optional for inpatient, prohibited for outpatient
            'bed_number' => [
                'nullable',
                'prohibited_if:admission_type,outpatient',
                'string',
                'max:50'
            ],
            'medical_officer' => 'nullable|string|max:255',
            
            // Initial assessment
            'initial_diagnosis' => 'nullable|string|max:500',
            'drug_allergy_noted' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'admission_date.required' => 'Admission date is required.',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future.',
            'admitted_for.required' => 'Reason for admission is required.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
            'police_case.in' => 'Police case must be yes or no.',
            'ward.required_if' => 'Ward is required for inpatient admissions.',
            'ward.prohibited_if' => 'Ward cannot be specified for outpatient admissions.',
            'bed_number.prohibited_if' => 'Bed number cannot be specified for outpatient admissions.',
        ];
    }
}

