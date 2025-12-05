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
            // Required for inpatient admission
            'ward' => 'required|string|max:100',
            'bed_number' => 'nullable|string|max:50',
            
            // Optional but useful
            'admission_time' => 'nullable|date_format:H:i',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ward.required' => 'Ward is required for inpatient admission.',
        ];
    }
}

