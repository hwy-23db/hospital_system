<?php

namespace App\Http\Requests\Api;

use App\Rules\MyanmarAddress;
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
            'admission_type' => 'required|in:outpatient,inpatient',

            // Staff assignment
            'doctor_id' => 'required|exists:users,id',
            'nurse_id' => 'required|exists:users,id',

            // Required admission details
            'admission_date' => 'required|date|before_or_equal:today',
            'admitted_for' => 'required|string|max:500',

            // Required admission details
            'admission_time' => 'required|date_format:H:i',
            // Address - accepts JSON string with {region, district, township} or plain text
            'present_address' => ['required', new MyanmarAddress()],
            'police_case' => 'required|string|in:yes,no',
            'service' => 'required|string|max:255',
            'initial_diagnosis' => 'required|string|max:500',

            // Optional admission details
            'referred_by' => 'nullable|string|max:255',
            // Ward: Required for inpatient, prohibited for outpatient
            'ward' => [
                'nullable',
                'required_if:admission_type,inpatient',
                'prohibited_if:admission_type,outpatient',
                'string',
                'max:100'
            ],
            // Bed number: Required for inpatient, prohibited for outpatient
            'bed_number' => [
                'nullable',
                'required_if:admission_type,inpatient',
                'prohibited_if:admission_type,outpatient',
                'string',
                'max:50'
            ],
            'medical_officer' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],

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
            'admission_type.required' => 'Admission type is required.',
            'admission_type.in' => 'Admission type must be outpatient or inpatient.',
            'doctor_id.required' => 'Doctor assignment is required.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.required' => 'Nurse assignment is required.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
            'admission_date.required' => 'Admission date is required.',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future.',
            'admission_time.required' => 'Admission time is required.',
            'admission_time.date_format' => 'Admission time must be in HH:mm format.',
            'admitted_for.required' => 'Reason for admission is required.',
            'present_address.required' => 'Present address is required.',
            'police_case.required' => 'Police case status is required.',
            'police_case.in' => 'Police case must be yes or no.',
            'service.required' => 'Service type is required.',
            'initial_diagnosis.required' => 'Initial diagnosis is required.',
            'ward.required_if' => 'Ward is required for inpatient admissions.',
            'ward.prohibited_if' => 'Ward cannot be specified for outpatient admissions.',
            'bed_number.required_if' => 'Bed number is required for inpatient admissions.',
            'bed_number.prohibited_if' => 'Bed number cannot be specified for outpatient admissions.',
            'medical_officer.regex' => 'Medical officer name can only contain letters, spaces, hyphens, apostrophes, and periods.',
        ];
    }
}
