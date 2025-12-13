<?php

namespace App\Http\Requests\Api;

use App\Models\TreatmentRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentRecordRequest extends FormRequest
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
                'sometimes',
                Rule::in(TreatmentRecord::treatmentTypes()),
            ],
            'treatment_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:1000',
            'medications' => 'sometimes|required|string|max:500',
            'dosage' => 'sometimes|required|string|max:255',
            'treatment_date' => 'sometimes|required|date',
            'treatment_time' => 'sometimes|required|date_format:H:i',
            'results' => 'sometimes|nullable|string|max:1000',
            'findings' => 'sometimes|nullable|string|max:1000',
            'outcome' => [
                'sometimes',
                'required',
                Rule::in(TreatmentRecord::outcomes()),
            ],
            'pre_procedure_notes' => 'sometimes|nullable|string|max:1000',
            'post_procedure_notes' => 'sometimes|nullable|string|max:1000',
            'complications' => 'sometimes|nullable|string|max:500',
            'doctor_id' => 'sometimes|nullable|exists:users,id',
            'nurse_id' => 'sometimes|nullable|exists:users,id',
            'attachments' => 'sometimes|nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf|max:5120', // 5MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'treatment_type.in' => 'Invalid treatment type.',
            'treatment_name.required' => 'Treatment name cannot be null when updating.',
            'medications.required' => 'Medications cannot be null when updating.',
            'dosage.required' => 'Dosage information cannot be null when updating.',
            'treatment_date.required' => 'Treatment date cannot be null when updating.',
            'treatment_time.required' => 'Treatment time cannot be null when updating.',
            'treatment_time.date_format' => 'Treatment time must be in HH:MM format.',
            'outcome.required' => 'Treatment outcome cannot be null when updating.',
            'outcome.in' => 'Invalid outcome value.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
        ];
    }
}
