<?php

namespace App\Http\Requests\Api;

use App\Models\TreatmentRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentRecordRequest extends FormRequest
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
            'treatment_type' => [
                'required',
                Rule::in(TreatmentRecord::treatmentTypes()),
            ],
            'treatment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'medications' => 'required|string|max:500',
            'dosage' => 'required|string|max:255',
            'treatment_date' => 'required|date',
            'treatment_time' => 'required|date_format:H:i',
            'results' => 'nullable|string|max:1000',
            'findings' => 'nullable|string|max:1000',
            'outcome' => [
                'required',
                Rule::in(TreatmentRecord::outcomes()),
            ],
            'pre_procedure_notes' => 'nullable|string|max:1000',
            'post_procedure_notes' => 'nullable|string|max:1000',
            'complications' => 'nullable|string|max:500',
            'doctor_id' => 'nullable|exists:users,id',
            'nurse_id' => 'nullable|exists:users,id',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf|max:5120', // 5MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'treatment_type.required' => 'Treatment type is required.',
            'treatment_type.in' => 'Invalid treatment type.',
            'treatment_name.required' => 'Treatment name is required.',
            'medications.required' => 'Medications are required.',
            'dosage.required' => 'Dosage information is required.',
            'treatment_date.required' => 'Treatment date is required.',
            'treatment_time.required' => 'Treatment time is required.',
            'treatment_time.date_format' => 'Treatment time must be in HH:MM format.',
            'outcome.required' => 'Treatment outcome is required.',
            'outcome.in' => 'Invalid outcome value.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
        ];
    }
}
