<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Requests\Api\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    /**
     * List patients (role-based).
     * - root_user/admission: See all patients
     * - doctor: See only patients assigned to them
     * - nurse: See only patients assigned to them
     * Returns demographic data with admission summary.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);
        $currentlyAdmitted = $request->query('currently_admitted');

        // Base query with admission count
        $query = Patient::withCount('admissions');

        // Role-based filtering
        if (in_array($user->role, ['root_user', 'admission'])) {
            // Root and admission can see all patients
            $query->with(['admissions' => function ($q) {
                $q->where('status', 'admitted')
                    ->select('id', 'patient_id', 'admission_number', 'admission_date', 'admitted_for', 'status', 'doctor_id', 'nurse_id')
                    ->with(['doctor:id,name', 'nurse:id,name']);
            }]);
        } elseif ($user->role === 'doctor') {
            // Doctors can only see patients they are assigned to
            $query->whereHas('admissions', function ($q) use ($user) {
                $q->where('doctor_id', $user->id);
            })->with(['admissions' => function ($q) use ($user) {
                $q->where('doctor_id', $user->id)
                    ->select('id', 'patient_id', 'admission_number', 'admission_date', 'admitted_for', 'status', 'doctor_id', 'nurse_id')
                    ->with(['doctor:id,name', 'nurse:id,name']);
            }]);
        } elseif ($user->role === 'nurse') {
            // Nurses can only see patients they are assigned to
            $query->whereHas('admissions', function ($q) use ($user) {
                $q->where('nurse_id', $user->id);
            })->with(['admissions' => function ($q) use ($user) {
                $q->where('nurse_id', $user->id)
                    ->select('id', 'patient_id', 'admission_number', 'admission_date', 'admitted_for', 'status', 'doctor_id', 'nurse_id')
                    ->with(['doctor:id,name', 'nurse:id,name']);
            }]);
        } else {
            // Unknown role - deny access
            return response()->json([
                'message' => 'Unauthorized. You do not have access to patient list.'
            ], 403);
        }

        // Apply search filter
        if ($search) {
            $query->search($search);
        }

        // Filter by currently admitted
        if ($currentlyAdmitted === 'true') {
            $query->currentlyAdmitted();
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Determine list type for response message
        $listType = in_array($user->role, ['root_user', 'admission']) ? 'all' : 'assigned';

        Log::info('Patient list accessed', [
            'user_id' => $user->id,
            'role' => $user->role,
            'list_type' => $listType,
            'total_results' => $patients->total(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patients retrieved successfully',
            'list_type' => $listType,
            'data' => $patients,
        ]);
    }

    /**
     * Search for patients by name, NRC, or phone.
     * Used by admission to check if patient already exists.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized. Only admission staff can search patients.'
            ], 403);
        }

        $search = $request->query('q');

        if (!$search || strlen($search) < 2) {
            return response()->json([
                'message' => 'Search query must be at least 2 characters.',
                'data' => []
            ], 400);
        }

        $patients = Patient::search($search)
            ->withCount('admissions')
            ->with(['admissions' => function ($q) {
                $q->where('status', 'admitted')
                    ->select('id', 'patient_id', 'admission_number', 'status');
            }])
            ->select('id', 'name', 'nrc_number', 'contact_phone', 'age', 'sex', 'dob')
            ->limit(20)
            ->get()
            ->map(function ($patient) {
                $patient->is_currently_admitted = $patient->admissions->where('status', 'admitted')->count() > 0;
                return $patient;
            });

        return response()->json([
            'message' => 'Search results',
            'total' => $patients->count(),
            'data' => $patients,
        ]);
    }

    /**
     * Store a new patient (demographic data only).
     * Only root_user and admission can create patients.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized. Only admission staff can register patients.'
            ], 403);
        }

        $patient = Patient::create($request->validated());

        Log::info('Patient registered', [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'registered_by' => $user->id,
            'registered_by_role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patient registered successfully',
            'data' => $patient,
        ], 201);
    }

    /**
     * Show a specific patient with admission history.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $patient = Patient::with([
            'admissions' => function ($q) {
                $q->orderBy('admission_date', 'desc')
                    ->with(['doctor:id,name,email', 'nurse:id,name,email']);
            },
            'admissions.treatmentRecords' => function ($q) {
                $q->orderBy('treatment_date', 'desc')
                    ->with(['doctor:id,name']);
            }
        ])->find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        // Check access based on role
        if (!$this->canAccessPatient($user, $patient)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this patient.'
            ], 403);
        }

        // For doctors/nurses, filter to only their admissions
        if (in_array($user->role, ['doctor', 'nurse'])) {
            $patient->admissions = $patient->admissions->filter(function ($admission) use ($user) {
                return $admission->doctor_id === $user->id || $admission->nurse_id === $user->id;
            })->values();
        }

        Log::info('Patient details viewed', [
            'patient_id' => $patient->id,
            'viewed_by' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patient retrieved successfully',
            'data' => $patient,
        ]);
    }

    /**
     * Update patient demographic information.
     * Only root_user and admission can update patient data.
     */
    public function update(UpdatePatientRequest $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized. Only admission staff can update patient demographic information.'
            ], 403);
        }

        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        $patient->update($request->validated());

        Log::info('Patient updated', [
            'patient_id' => $patient->id,
            'updated_by' => $user->id,
            'role' => $user->role,
            'fields_updated' => array_keys($request->validated()),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Patient updated successfully',
            'data' => $patient->fresh(),
        ]);
    }

    /**
     * Delete a patient (soft delete).
     * Only root_user can delete patients.
     *
     * COMMENTED OUT: Patient deletion is disabled for data integrity.
     * Deceased patients should never be deleted for legal/medical compliance.
     */
    // public function destroy(Request $request, $id): JsonResponse
    // {
    //     $user = $request->user();

    //     if ($user->role !== 'root_user') {
    //         return response()->json([
    //             'message' => 'Unauthorized. Only root user can delete patients.'
    //         ], 403);
    //     }

    //     $patient = Patient::withCount('admissions')->find($id);

    //     if (!$patient) {
    //         return response()->json([
    //             'message' => 'Patient not found.'
    //         ], 404);
    //     }

    //     // Check if patient has active admissions
    //     if ($patient->isAdmitted()) {
    //         return response()->json([
    //             'message' => 'Cannot delete patient with active admission. Please discharge first.'
    //         ], 400);
    //     }

    //     // CRITICAL: Prevent deleting deceased patients (medical/legal record retention)
    //     if ($patient->isDeceased()) {
    //         $deathRecord = $patient->getDeathRecord();
    //         return response()->json([
    //             'message' => 'Cannot delete deceased patient. Medical records must be retained.',
    //             'death_record' => [
    //                 'admission_number' => $deathRecord->admission_number,
    //                 'date_of_death' => $deathRecord->discharge_date,
    //                 'cause_of_death' => $deathRecord->cause_of_death,
    //             ],
    //             'note' => 'Deceased patient records are protected for legal and medical compliance.'
    //         ], 400);
    //     }

    //     $patientInfo = [
    //         'id' => $patient->id,
    //         'name' => $patient->name,
    //         'nrc_number' => $patient->nrc_number,
    //         'total_admissions' => $patient->admissions_count,
    //     ];

    //     $patient->delete();

    //     Log::info('Patient deleted', [
    //         'patient_id' => $patientInfo['id'],
    //         'patient_name' => $patientInfo['name'],
    //         'deleted_by' => $user->id,
    //         'ip' => $request->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Patient deleted successfully (soft delete)',
    //         'deleted_patient' => $patientInfo,
    //     ]);
    // }

    /**
     * Get patient's admission history.
     */
    public function admissionHistory(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        if (!$this->canAccessPatient($user, $patient)) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $query = $patient->admissions()
            ->with(['doctor:id,name,email', 'nurse:id,name,email'])
            ->withCount('treatmentRecords');

        // For doctors/nurses, filter to only their admissions
        if ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        } elseif ($user->role === 'nurse') {
            $query->where('nurse_id', $user->id);
        }

        $admissions = $query->orderBy('admission_date', 'desc')->get();

        return response()->json([
            'message' => 'Admission history retrieved successfully',
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'total_admissions' => $admissions->count(),
            'data' => $admissions,
        ]);
    }

    /**
     * Get list of doctors for assignment.
     */
    public function getDoctors(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $doctors = User::where('role', 'doctor')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Doctors retrieved successfully',
            'data' => $doctors,
        ]);
    }

    /**
     * Get list of nurses for assignment.
     */
    public function getNurses(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $nurses = User::where('role', 'nurse')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Nurses retrieved successfully',
            'data' => $nurses,
        ]);
    }

    /**
     * Check if user can access a patient's data.
     */
    private function canAccessPatient($user, Patient $patient): bool
    {
        if (in_array($user->role, ['root_user', 'admission'])) {
            return true;
        }

        // Doctors and nurses can access if they're assigned to any admission of this patient
        if ($user->role === 'doctor') {
            return $patient->admissions()->where('doctor_id', $user->id)->exists();
        }

        if ($user->role === 'nurse') {
            return $patient->admissions()->where('nurse_id', $user->id)->exists();
        }

        return false;
    }
}
