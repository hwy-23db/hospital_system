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
            'treatment_name' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:1000',
            'medications' => 'sometimes|nullable|string|max:500',
            'dosage' => 'sometimes|nullable|string|max:255',
            'treatment_date' => 'sometimes|nullable|date',
            'treatment_time' => 'sometimes|nullable|date_format:H:i',
            'results' => 'sometimes|nullable|string|max:1000',
            'findings' => 'sometimes|nullable|string|max:1000',
            'outcome' => [
                'sometimes',
                'nullable',
                Rule::in(TreatmentRecord::outcomes()),
            ],
            'pre_procedure_notes' => 'sometimes|nullable|string|max:1000',
            'post_procedure_notes' => 'sometimes|nullable|string|max:1000',
            'complications' => 'sometimes|nullable|string|max:500',
            'doctor_id' => 'sometimes|nullable|exists:users,id',
            'nurse_id' => 'sometimes|nullable|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'treatment_type.in' => 'Invalid treatment type.',
            'outcome.in' => 'Invalid outcome value.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
        ];
    }
}
