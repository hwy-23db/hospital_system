<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToInpatientRequest extends FormRequest
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
     * These are the fields needed to convert outpatient to inpatient.
     */
    public function rules(): array
    {
        return [
            // All fields required for inpatient admission
            'ward' => 'required|string|max:100',
            'bed_number' => 'required|string|max:50',
            'admission_time' => 'required|date_format:H:i',
            'remarks' => 'required|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ward.required' => 'Ward is required for inpatient admission.',
            'bed_number.required' => 'Bed number is required for inpatient admission.',
            'admission_time.required' => 'Admission time is required for inpatient admission.',
            'admission_time.date_format' => 'Admission time must be in HH:MM format.',
            'remarks.required' => 'Remarks are required for inpatient admission.',
        ];
    }
}
