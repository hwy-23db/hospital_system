<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdmissionRequest;
use App\Http\Requests\Api\UpdateAdmissionRequest;
use App\Http\Requests\Api\ConvertToInpatientRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdmissionController extends Controller
{
    /**
     * List admissions based on user role.
     * - Root/Admission: See all admissions
     * - Doctor: See only assigned admissions
     * - Nurse: See only assigned admissions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status'); // admitted, discharged, deceased, transferred
        $admissionType = $request->query('admission_type'); // inpatient, outpatient
        $perPage = $request->query('per_page', 15);

        $query = Admission::with([
            'patient:id,name,nrc_number,contact_phone,age,sex',
            'doctor:id,name,email',
            'nurse:id,name,email'
        ])->withCount('treatmentRecords');

        // Role-based filtering
        switch ($user->role) {
            case 'root_user':
            case 'admission':
                // Full access to all admissions
                break;
            case 'doctor':
                $query->forDoctor($user->id);
                break;
            case 'nurse':
                $query->forNurse($user->id);
                break;
            default:
                return response()->json([
                    'message' => 'Unauthorized. Your role does not have access to admission data.'
                ], 403);
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply admission type filter (inpatient/outpatient)
        if ($admissionType) {
            if (!in_array($admissionType, ['inpatient', 'outpatient'])) {
                return response()->json([
                    'message' => 'Invalid admission_type. Must be "inpatient" or "outpatient".'
                ], 400);
            }
            $query->where('admission_type', $admissionType);
        }

        $admissions = $query->orderBy('admission_date', 'desc')->paginate($perPage);

        Log::info('Admission list accessed', [
            'user_id' => $user->id,
            'role' => $user->role,
            'status_filter' => $status,
            'admission_type_filter' => $admissionType,
            'total_results' => $admissions->total(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Admissions retrieved successfully',
            'data' => $admissions,
        ]);
    }

    /**
     * Create a new admission for a patient.
     * Only root_user and admission can create admissions.
     */
    public function store(StoreAdmissionRequest $request, $patientId): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized. Only admission staff can create admissions.'
            ], 403);
        }

        $patient = Patient::find($patientId);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        // Get admission type from request or default to inpatient
        $admissionType = $request->input('admission_type', 'inpatient');

        // CRITICAL: Check if patient is deceased - cannot admit a deceased patient
        $deceasedAdmission = $patient->admissions()
            ->where('status', 'deceased')
            ->first();

        if ($deceasedAdmission) {
            return response()->json([
                'message' => 'Cannot create admission. Patient is deceased.',
                'deceased_record' => [
                    'admission_number' => $deceasedAdmission->admission_number,
                    'date_of_death' => $deceasedAdmission->discharge_date,
                    'cause_of_death' => $deceasedAdmission->cause_of_death,
                ]
            ], 400);
        }

        // Check if patient already has an active INPATIENT admission
        // Outpatient visits don't block new admissions
        $activeInpatient = $patient->admissions()
            ->where('status', 'admitted')
            ->where('admission_type', 'inpatient')
            ->first();

        if ($activeInpatient && $admissionType === 'inpatient') {
            return response()->json([
                'message' => 'Patient already has an active inpatient admission.',
                'current_admission' => [
                    'id' => $activeInpatient->id,
                    'admission_number' => $activeInpatient->admission_number,
                    'admission_date' => $activeInpatient->admission_date,
                    'admitted_for' => $activeInpatient->admitted_for,
                ]
            ], 400);
        }

        // Validate doctor_id is actually a doctor
        if ($request->has('doctor_id') && $request->doctor_id) {
            $doctor = User::find($request->doctor_id);
            if (!$doctor || $doctor->role !== 'doctor') {
                return response()->json([
                    'message' => 'The selected user is not a doctor.'
                ], 422);
            }
        }

        // Validate nurse_id is actually a nurse
        if ($request->has('nurse_id') && $request->nurse_id) {
            $nurse = User::find($request->nurse_id);
            if (!$nurse || $nurse->role !== 'nurse') {
                return response()->json([
                    'message' => 'The selected user is not a nurse.'
                ], 422);
            }
        }

        $data = $request->validated();
        $data['patient_id'] = $patientId;
        $data['admission_type'] = $admissionType;

        $admission = Admission::create($data);

        $messageType = $admission->isOutpatient() ? 'outpatient visit' : 'inpatient admission';

        Log::info('Admission created', [
            'admission_id' => $admission->id,
            'admission_number' => $admission->admission_number,
            'admission_type' => $admission->admission_type,
            'patient_id' => $patientId,
            'patient_name' => $patient->name,
            'admitted_for' => $admission->admitted_for,
            'created_by' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patient registered successfully as ' . $messageType,
            'data' => $admission->load([
                'patient:id,name,nrc_number,contact_phone',
                'doctor:id,name,email',
                'nurse:id,name,email'
            ]),
        ], 201);
    }

    /**
     * Show a specific admission.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $admission = Admission::with([
            'patient',
            'doctor:id,name,email',
            'nurse:id,name,email',
            'treatmentRecords' => function ($q) {
                $q->orderBy('treatment_date', 'desc')
                    ->with(['doctor:id,name', 'nurse:id,name']);
            }
        ])->find($id);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Check access
        if (!$this->canAccessAdmission($user, $admission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this admission.'
            ], 403);
        }

        // Add computed attributes
        $admission->length_of_stay = $admission->length_of_stay;

        Log::info('Admission details viewed', [
            'admission_id' => $admission->id,
            'viewed_by' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Admission retrieved successfully',
            'data' => $admission,
        ]);
    }

    /**
     * Update an admission.
     * - Admission staff: Can update admission details, staff assignment
     * - Doctor: Can update medical info for assigned admissions
     */
    public function update(UpdateAdmissionRequest $request, $id): JsonResponse
    {
        $user = $request->user();

        $admission = Admission::find($id);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Check permissions
        if (!$this->canUpdateAdmission($user, $admission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission to update this admission.'
            ], 403);
        }

        // Validate doctor_id if being updated
        if ($request->has('doctor_id') && $request->doctor_id) {
            $doctor = User::find($request->doctor_id);
            if (!$doctor || $doctor->role !== 'doctor') {
                return response()->json([
                    'message' => 'The selected user is not a doctor.'
                ], 422);
            }
        }

        // Validate nurse_id if being updated
        if ($request->has('nurse_id') && $request->nurse_id) {
            $nurse = User::find($request->nurse_id);
            if (!$nurse || $nurse->role !== 'nurse') {
                return response()->json([
                    'message' => 'The selected user is not a nurse.'
                ], 422);
            }
        }

        // CRITICAL: Block ward/bed for outpatient admissions
        if ($admission->admission_type === 'outpatient') {
            if ($request->has('ward') && $request->ward) {
                return response()->json([
                    'message' => 'Ward cannot be specified for outpatient admissions.',
                    'admission_type' => 'outpatient'
                ], 422);
            }
            if ($request->has('bed_number') && $request->bed_number) {
                return response()->json([
                    'message' => 'Bed number cannot be specified for outpatient admissions.',
                    'admission_type' => 'outpatient'
                ], 422);
            }
        }

        // For doctors, restrict which fields they can update
        $validatedData = $request->validated();
        if ($user->role === 'doctor') {
            $allowedFields = [
                'initial_diagnosis',
                'drug_allergy_noted',
                'remarks',
                'discharge_date',
                'discharge_time',
                'discharge_diagnosis',
                'other_diagnosis',
                'external_cause_of_injury',
                'clinician_summary',
                'surgical_procedure',
                'discharge_type',
                'discharge_status',
                'discharge_instructions',
                'follow_up_instructions',
                'follow_up_date',
                'cause_of_death',
                'autopsy',
                'time_of_death',
                'certified_by',
                'approved_by',
                'attending_doctor_name',
                'attending_doctor_signature',
                'status'
            ];
            $validatedData = array_intersect_key($validatedData, array_flip($allowedFields));
        }

        $admission->update($validatedData);

        Log::info('Admission updated', [
            'admission_id' => $admission->id,
            'updated_by' => $user->id,
            'role' => $user->role,
            'fields_updated' => array_keys($validatedData),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Admission updated successfully',
            'data' => $admission->fresh()->load([
                'patient:id,name,nrc_number',
                'doctor:id,name,email',
                'nurse:id,name,email'
            ]),
        ]);
    }

    /**
     * Discharge a patient.
     * Only doctors can discharge patients they are assigned to.
     */
    public function discharge(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::find($id);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Only doctors and root can discharge
        if (!in_array($user->role, ['root_user', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors can discharge patients.'
            ], 403);
        }

        // Doctors can only discharge their assigned patients
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only discharge patients assigned to you.'
            ], 403);
        }

        // Check if patient is deceased (explicit check for clarity)
        if ($admission->status === 'deceased') {
            return response()->json([
                'message' => 'Cannot discharge. Patient is deceased. Use confirm death endpoint instead.'
            ], 400);
        }

        // Check if already discharged or transferred
        if ($admission->status !== 'admitted') {
            return response()->json([
                'message' => 'Patient is not currently admitted. Current status: ' . $admission->status
            ], 400);
        }

        $request->validate([
            'discharge_type' => 'required|in:normal,against_advice,absconded,transferred',
            'discharge_status' => 'required|in:improved,unchanged,worse',
            'discharge_diagnosis' => 'required|string|max:500',
            'clinician_summary' => 'required|string|max:1000',
            'discharge_instructions' => 'required|string|max:1000',
            'follow_up_instructions' => 'required|string|max:500',
            'follow_up_date' => 'required|date|after_or_equal:today',
        ]);

        $admission->update([
            'status' => 'discharged',
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_type' => $request->discharge_type,
            'discharge_status' => $request->discharge_status,
            'discharge_diagnosis' => $request->discharge_diagnosis,
            'clinician_summary' => $request->clinician_summary,
            'discharge_instructions' => $request->discharge_instructions,
            'follow_up_instructions' => $request->follow_up_instructions,
            'follow_up_date' => $request->follow_up_date,
            'attending_doctor_name' => $user->name,
        ]);

        Log::info('Patient discharged', [
            'admission_id' => $admission->id,
            'admission_number' => $admission->admission_number,
            'patient_id' => $admission->patient_id,
            'discharged_by' => $user->id,
            'discharge_type' => $request->discharge_type,
            'length_of_stay' => $admission->length_of_stay,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patient discharged successfully',
            'data' => $admission->fresh()->load([
                'patient:id,name,nrc_number',
                'doctor:id,name,email',
                'nurse:id,name,email'
            ]),
        ]);
    }

    /**
     * Confirm patient death.
     * Only doctors can confirm death for patients they are assigned to.
     */
    public function confirmDeath(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::with('patient')->find($id);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Only doctors and root can confirm death
        if (!in_array($user->role, ['root_user', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors can confirm death.'
            ], 403);
        }

        // Doctors can only confirm death for their assigned patients
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only confirm death for patients assigned to you.'
            ], 403);
        }

        // CRITICAL: Check if patient is ALREADY deceased from another admission
        $existingDeath = $admission->patient->admissions()
            ->where('status', 'deceased')
            ->where('id', '!=', $id) // Exclude current admission
            ->first();

        if ($existingDeath) {
            return response()->json([
                'message' => 'Patient death has already been confirmed in a previous admission.',
                'previous_death_record' => [
                    'admission_number' => $existingDeath->admission_number,
                    'date_of_death' => $existingDeath->discharge_date,
                    'cause_of_death' => $existingDeath->cause_of_death,
                ]
            ], 400);
        }

        // Check if THIS admission is already processed
        if ($admission->status === 'deceased') {
            return response()->json([
                'message' => 'Death has already been confirmed for this admission.'
            ], 400);
        }

        // CRITICAL: Cannot confirm death on a discharged admission
        // If patient was discharged (alive), they cannot be marked as deceased retroactively
        // Death must be confirmed on ACTIVE admissions only
        if ($admission->status === 'discharged') {
            return response()->json([
                'message' => 'Cannot confirm death on a discharged admission. Death can only be confirmed for active admissions (status: admitted).',
                'current_status' => 'discharged',
                'discharge_date' => $admission->discharge_date,
                'note' => 'If patient died after discharge, this should be recorded in a new admission or through proper medical records, not by modifying a discharged admission.'
            ], 400);
        }

        // CRITICAL: Cannot confirm death on transferred admissions
        if ($admission->status === 'transferred') {
            return response()->json([
                'message' => 'Cannot confirm death on a transferred admission. Death can only be confirmed for active admissions (status: admitted).',
                'current_status' => 'transferred',
                'note' => 'Patient was transferred to another facility. Death confirmation should be handled by the receiving facility.'
            ], 400);
        }

        // CRITICAL: Can only confirm death on ACTIVE admissions
        if ($admission->status !== 'admitted') {
            return response()->json([
                'message' => 'Death can only be confirmed for active admissions (status: admitted). Current status: ' . $admission->status
            ], 400);
        }

        $request->validate([
            'cause_of_death' => 'required|string|max:255',
            'autopsy' => 'required|in:yes,no,pending',
            'time_of_death' => 'required|date',
            'certified_by' => 'required|string|max:255',
        ]);

        $admission->update([
            'status' => 'deceased',
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_status' => 'dead',
            'cause_of_death' => $request->cause_of_death,
            'autopsy' => $request->autopsy ?? 'pending',
            'time_of_death' => $request->time_of_death ?? now(),
            'certified_by' => $request->certified_by ?? $user->name,
            'attending_doctor_name' => $user->name,
        ]);

        // CRITICAL: Close all other active admissions for this patient
        // IMPORTANT: We do NOT mark them as "deceased" - only the admission where death
        // was confirmed gets status="deceased". Other admissions are closed as "discharged"
        // with discharge_status="dead" so we know which admission the patient actually died in.
        $otherActiveAdmissions = $admission->patient->admissions()
            ->where('id', '!=', $admission->id) // Exclude the admission where death was confirmed
            ->where('status', 'admitted')
            ->get();

        $closedAdmissions = [];
        foreach ($otherActiveAdmissions as $otherAdmission) {
            $otherAdmission->update([
                'status' => 'discharged', // NOT "deceased" - only the death admission is "deceased"
                'discharge_date' => now()->toDateString(),
                'discharge_time' => now()->format('H:i'),
                'discharge_type' => 'normal',
                'discharge_status' => 'dead', // Indicates patient died, but not in this admission
                'remarks' => 'Automatically closed due to patient death confirmed in admission ' . $admission->admission_number . '. Patient died in admission ' . $admission->admission_number . ', not in this admission.',
            ]);
            $closedAdmissions[] = [
                'id' => $otherAdmission->id,
                'admission_number' => $otherAdmission->admission_number,
                'admission_type' => $otherAdmission->admission_type,
                'status' => 'discharged', // Closed, not deceased
                'note' => 'Closed automatically - patient died in admission ' . $admission->admission_number,
            ];
        }

        Log::info('Patient death confirmed', [
            'admission_id' => $admission->id,
            'admission_number' => $admission->admission_number,
            'patient_id' => $admission->patient_id,
            'confirmed_by' => $user->id,
            'cause_of_death' => $request->cause_of_death,
            'other_admissions_closed' => count($closedAdmissions),
            'closed_admissions' => $closedAdmissions,
            'ip' => $request->ip(),
        ]);

        $response = [
            'message' => 'Patient death confirmed',
            'data' => $admission->fresh()->load([
                'patient:id,name,nrc_number',
                'doctor:id,name,email',
                'nurse:id,name,email'
            ]),
        ];

        // Include information about other admissions that were automatically closed
        if (count($closedAdmissions) > 0) {
            $response['other_admissions_closed'] = count($closedAdmissions);
            $response['closed_admissions'] = $closedAdmissions;
            $response['note'] = 'Other active admissions have been automatically closed (status: discharged, discharge_status: dead). Only the admission where death was confirmed has status: deceased.';
        }

        return response()->json($response);
    }

    /**
     * Assign staff to an admission.
     * REMOVED: Staff assignment is now handled via the main update endpoint.
     * Use PUT/PATCH /api/admissions/{id} with doctor_id and/or nurse_id fields.
     */
    // public function assignStaff(Request $request, $id): JsonResponse
    // {
    //     // This endpoint has been removed. Use the main update endpoint instead:
    //     // PUT/PATCH /api/admissions/{id} with { "doctor_id": X, "nurse_id": Y }
    // }

    /**
     * Delete an admission (soft delete).
     * DISABLED: Admission deletion is not allowed for data integrity and medical record retention.
     * Admission records must be permanently retained for:
     * - Medical and legal compliance
     * - Historical record integrity
     * - Audit trail requirements
     * - Treatment record preservation
     */
    // public function destroy(Request $request, $id): JsonResponse
    // {
    //     $user = $request->user();

    //     if ($user->role !== 'root_user') {
    //         return response()->json([
    //             'message' => 'Unauthorized. Only root user can delete admissions.'
    //         ], 403);
    //     }

    //     $admission = Admission::withCount('treatmentRecords')->find($id);

    //     if (!$admission) {
    //         return response()->json([
    //             'message' => 'Admission not found.'
    //         ], 404);
    //     }

    //     // Prevent deleting active admissions
    //     if ($admission->status === 'admitted') {
    //         return response()->json([
    //             'message' => 'Cannot delete an active admission. Please discharge the patient first.'
    //         ], 400);
    //     }

    //     $admissionInfo = [
    //         'id' => $admission->id,
    //         'admission_number' => $admission->admission_number,
    //         'patient_id' => $admission->patient_id,
    //         'treatment_records_count' => $admission->treatment_records_count,
    //     ];

    //     $admission->delete();

    //     Log::info('Admission deleted', [
    //         'admission_id' => $admissionInfo['id'],
    //         'admission_number' => $admissionInfo['admission_number'],
    //         'deleted_by' => $user->id,
    //         'ip' => $request->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Admission deleted successfully (soft delete)',
    //         'deleted_admission' => $admissionInfo,
    //     ]);
    // }

    /**
     * Get admission statistics.
     * Only root_user and admission can access.
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $stats = [
            'total_admissions' => Admission::count(),
            'currently_admitted' => Admission::where('status', 'admitted')->count(),
            'currently_admitted_inpatient' => Admission::where('status', 'admitted')
                ->where('admission_type', 'inpatient')->count(),
            'currently_admitted_outpatient' => Admission::where('status', 'admitted')
                ->where('admission_type', 'outpatient')->count(),
            'discharged_this_month' => Admission::where('status', 'discharged')
                ->whereMonth('discharge_date', now()->month)
                ->whereYear('discharge_date', now()->year)
                ->count(),
            'admissions_this_month' => Admission::whereMonth('admission_date', now()->month)
                ->whereYear('admission_date', now()->year)
                ->count(),
            'by_status' => Admission::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_type' => Admission::selectRaw('admission_type, count(*) as count')
                ->groupBy('admission_type')
                ->pluck('count', 'admission_type'),
        ];

        return response()->json([
            'message' => 'Statistics retrieved successfully',
            'data' => $stats,
        ]);
    }

    /**
     * Convert outpatient visit to inpatient admission.
     * Only admission staff and root can convert.
     */
    public function convertToInpatient(ConvertToInpatientRequest $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only admission staff or doctors can convert to inpatient.'
            ], 403);
        }

        $admission = Admission::with('patient')->find($id);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // CRITICAL: Check if patient is deceased
        if ($admission->patient->isDeceased()) {
            $deathRecord = $admission->patient->getDeathRecord();
            return response()->json([
                'message' => 'Cannot convert to inpatient. Patient is deceased.',
                'death_record' => [
                    'admission_number' => $deathRecord->admission_number,
                    'date_of_death' => $deathRecord->discharge_date,
                    'cause_of_death' => $deathRecord->cause_of_death,
                ]
            ], 400);
        }

        // Check if already inpatient
        if ($admission->isInpatient()) {
            return response()->json([
                'message' => 'This admission is already an inpatient admission.'
            ], 400);
        }

        // Check if outpatient visit is still active
        if ($admission->status !== 'admitted') {
            return response()->json([
                'message' => 'Cannot convert a closed outpatient visit. Current status: ' . $admission->status
            ], 400);
        }

        // CRITICAL: Check if patient already has an active INPATIENT admission
        // Only ONE active inpatient is allowed per patient
        $activeInpatient = $admission->patient->admissions()
            ->where('id', '!=', $admission->id) // Exclude the current admission being converted
            ->where('status', 'admitted')
            ->where('admission_type', 'inpatient')
            ->first();

        if ($activeInpatient) {
            return response()->json([
                'message' => 'Cannot convert to inpatient. Patient already has an active inpatient admission.',
                'current_inpatient' => [
                    'id' => $activeInpatient->id,
                    'admission_number' => $activeInpatient->admission_number,
                    'admission_date' => $activeInpatient->admission_date,
                    'admitted_for' => $activeInpatient->admitted_for,
                    'ward' => $activeInpatient->ward,
                    'bed_number' => $activeInpatient->bed_number,
                ],
                'note' => 'Please discharge the existing inpatient admission first, or convert this outpatient visit after the current inpatient is closed.'
            ], 400);
        }

        // For doctors, must be assigned to this admission
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only convert admissions assigned to you.'
            ], 403);
        }

        // Convert to inpatient
        $admission->convertToInpatient($request->validated());

        Log::info('Outpatient converted to inpatient', [
            'admission_id' => $admission->id,
            'admission_number' => $admission->admission_number,
            'patient_id' => $admission->patient_id,
            'converted_by' => $user->id,
            'ward' => $admission->ward,
            'bed_number' => $admission->bed_number,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Outpatient visit successfully converted to inpatient admission',
            'data' => $admission->fresh()->load([
                'patient:id,name,nrc_number',
                'doctor:id,name,email',
                'nurse:id,name,email'
            ]),
        ]);
    }

    /**
     * Check if user can access an admission.
     */
    private function canAccessAdmission($user, Admission $admission): bool
    {
        return match ($user->role) {
            'root_user', 'admission' => true,
            'doctor' => $admission->doctor_id === $user->id,
            'nurse' => $admission->nurse_id === $user->id,
            default => false,
        };
    }

    /**
     * Check if user can update an admission.
     */
    private function canUpdateAdmission($user, Admission $admission): bool
    {
        return match ($user->role) {
            'root_user', 'admission' => true,
            'doctor' => $admission->doctor_id === $user->id,
            'nurse' => false,
            default => false,
        };
    }
}
