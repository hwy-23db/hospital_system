<?php

namespace App\Http\Requests\Api;

use App\Models\Admission;
use App\Rules\MyanmarAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdmissionRequest extends FormRequest
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
     *
     * IMPORTANT: Certain fields are NOT allowed via this endpoint:
     * - status: Use dedicated endpoints (discharge, confirm-death)
     * - admission_type: Use POST /admissions/{id}/convert-to-inpatient
     * - Death info: Use POST /admissions/{id}/confirm-death
     * - Discharge type/status: Use POST /admissions/{id}/discharge
     */
    public function rules(): array
    {
        return [
            // Staff assignment
            'doctor_id' => 'sometimes|required|exists:users,id',
            'nurse_id' => 'sometimes|required|exists:users,id',

            // Admission details (ONLY for active admissions)
            'admission_date' => 'sometimes|date',
            'admission_time' => 'sometimes|required|date_format:H:i',
            // Address - accepts JSON string with {region, district, township} or plain text
            'present_address' => ['sometimes', 'required', new MyanmarAddress()],
            'admitted_for' => 'sometimes|string|max:500',
            'referred_by' => 'sometimes|nullable|string|max:255',
            'police_case' => 'sometimes|nullable|string|in:yes,no',
            'service' => 'sometimes|nullable|string|max:255',
            'ward' => 'sometimes|nullable|string|max:100',
            'bed_number' => 'sometimes|nullable|string|max:50',
            'medical_officer' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],

            // Medical Assessment (doctors can update for assigned admissions)
            'initial_diagnosis' => 'sometimes|nullable|string|max:500',
            'drug_allergy_noted' => 'sometimes|nullable|string|max:255',
            'remarks' => 'sometimes|nullable|string|max:500',
            'clinician_summary' => 'sometimes|nullable|string|max:1000',
            'surgical_procedure' => 'sometimes|nullable|string|max:500',
            'other_diagnosis' => 'sometimes|nullable|string|max:500',
            'external_cause_of_injury' => 'sometimes|nullable|string|max:500',
            'discharge_diagnosis' => 'sometimes|nullable|string|max:500',

            // Follow-up information (can be updated even after discharge)
            'discharge_instructions' => 'sometimes|nullable|string|max:1000',
            'follow_up_instructions' => 'sometimes|nullable|string|max:500',
            'follow_up_date' => 'sometimes|nullable|date|after_or_equal:today',

            // Certification info (for record keeping)
            'attending_doctor_name' => 'sometimes|nullable|string|max:255',
            'attending_doctor_signature' => 'sometimes|nullable|string|max:255',

            // ================================================================
            // BLOCKED FIELDS - Must use dedicated endpoints
            // ================================================================
            // 'status' - BLOCKED: Use /discharge or /confirm-death endpoints
            // 'admission_type' - BLOCKED: Use /convert-to-inpatient endpoint
            // 'discharge_type' - BLOCKED: Use /discharge endpoint
            // 'discharge_status' - BLOCKED: Use /discharge endpoint
            // 'discharge_date' - BLOCKED: Use /discharge endpoint
            // 'discharge_time' - BLOCKED: Use /discharge endpoint
            // 'cause_of_death' - BLOCKED: Use /confirm-death endpoint
            // 'time_of_death' - BLOCKED: Use /confirm-death endpoint
            // 'autopsy' - BLOCKED: Use /confirm-death endpoint
            // 'certified_by' - BLOCKED: Set by /discharge or /confirm-death
            // 'approved_by' - BLOCKED: Set by /discharge or /confirm-death
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Doctor assignment cannot be null when updating.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'nurse_id.required' => 'Nurse assignment cannot be null when updating.',
            'nurse_id.exists' => 'The selected nurse does not exist.',
            'admission_time.required' => 'Admission time cannot be null when updating.',
            'admission_time.date_format' => 'Admission time must be in HH:mm format.',
            'present_address.required' => 'Present address cannot be null when updating.',
            'follow_up_date.after_or_equal' => 'Follow-up date must be today or in the future.',
            'medical_officer.regex' => 'Medical officer name can only contain letters, spaces, hyphens, apostrophes, and periods.',
        ];
    }

    /**
     * Handle a passed validation attempt.
     * Remove any blocked fields that might have been submitted.
     */
    protected function passedValidation(): void
    {
        // Fields that MUST be set via dedicated endpoints only
        $blockedFields = [
            'status',           // Use /discharge or /confirm-death
            'admission_type',   // Use /convert-to-inpatient
            'discharge_type',   // Use /discharge
            'discharge_status', // Use /discharge
            'discharge_date',   // Use /discharge or /confirm-death
            'discharge_time',   // Use /discharge or /confirm-death
            'cause_of_death',   // Use /confirm-death
            'time_of_death',    // Use /confirm-death
            'autopsy',          // Use /confirm-death
            'certified_by',     // Set by endpoints
            'approved_by',      // Set by endpoints
        ];

        // Remove blocked fields from validated data
        foreach ($blockedFields as $field) {
            if ($this->has($field)) {
                // Log attempt to use blocked field
                \Illuminate\Support\Facades\Log::warning('Blocked field submitted to Update Admission', [
                    'field' => $field,
                    'value' => $this->input($field),
                    'user_id' => $this->user()?->id,
                    'admission_id' => $this->route('id'),
                ]);
            }
        }

        // Replace input with only allowed fields
        $this->replace(
            collect($this->validated())->except($blockedFields)->toArray()
        );
    }
}
