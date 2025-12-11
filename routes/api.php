<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\TreatmentRecordController;

// Login endpoint - rate limiting is handled in LoginRequest class
// 5 attempts per email+IP combination with 60 second lockout
Route::post('/login', [AuthController::class, 'login']);

// Note: CSRF token endpoint is not needed for token-based API authentication
// For stateful SPA authentication, use Sanctum's built-in endpoint: GET /sanctum/csrf-cookie

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Get current authenticated user
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    });

    // Logout endpoint
    Route::post('/logout', [AuthController::class, 'logout']);

    // Update profile endpoint - Users can update their own profile
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/user/profile', [AuthController::class, 'updateProfile']);

    // Registration endpoint - ONLY accessible by root_user
    // Root user can create other users (admission, nurse, doctor)
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('root_user');

    // User management endpoints - ONLY accessible by root_user
    Route::middleware('root_user')->group(function () {
        // List users (active by default, add ?deleted=true for deleted users)
        Route::get('/users', [AuthController::class, 'index']);

        // Send password reset link to a user
        Route::post('/users/forgot-password', [AuthController::class, 'sendPasswordResetLink']);

        // Delete a user (soft delete)
        Route::delete('/users/{id}', [AuthController::class, 'destroy']);

        // Restore a soft-deleted user
        Route::post('/users/{id}/restore', [AuthController::class, 'restore']);
    });

    // ==========================================
    // HELPER ENDPOINTS
    // ==========================================

    // Staff lists (admission/root only)
    Route::get('/staff/doctors', [PatientController::class, 'getDoctors']);
    Route::get('/staff/nurses', [PatientController::class, 'getNurses']);

    // Treatment options (all authenticated users)
    Route::get('/treatment-options/types', [TreatmentRecordController::class, 'getTreatmentTypes']);
    Route::get('/treatment-options/outcomes', [TreatmentRecordController::class, 'getOutcomes']);

    // Myanmar address data (all authenticated users - for patient and admission forms)
    Route::get('/addresses/myanmar', [PatientController::class, 'getMyanmarAddresses']);

    // ==========================================
    // PATIENT MANAGEMENT (Demographic Data)
    // ==========================================

    // Patient search (admission/root only - for checking if patient exists)
    Route::get('/patients/search', [PatientController::class, 'search']);

    // Patient CRUD operations (demographic data only)
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::patch('/patients/{id}', [PatientController::class, 'update']);
    // Route::delete('/patients/{id}', [PatientController::class, 'destroy']); // DISABLED: Patient deletion not allowed

    // Patient admission history
    Route::get('/patients/{id}/admissions', [PatientController::class, 'admissionHistory']);

    // ==========================================
    // ADMISSION MANAGEMENT (Hospital Stays)
    // ==========================================

    // Admission statistics (admission/root only)
    Route::get('/admissions/statistics', [AdmissionController::class, 'statistics']);

    // List all admissions (role-based)
    Route::get('/admissions', [AdmissionController::class, 'index']);

    // Create new admission for a patient
    Route::post('/patients/{patientId}/admit', [AdmissionController::class, 'store']);

    // Admission CRUD operations
    Route::get('/admissions/{id}', [AdmissionController::class, 'show']);
    Route::put('/admissions/{id}', [AdmissionController::class, 'update']);
    Route::patch('/admissions/{id}', [AdmissionController::class, 'update']);
    // Route::delete('/admissions/{id}', [AdmissionController::class, 'destroy']); // DISABLED: Admission deletion not allowed

    // Discharge patient (doctor/root only)
    Route::post('/admissions/{id}/discharge', [AdmissionController::class, 'discharge']);

    // Confirm death (doctor/root only)
    Route::post('/admissions/{id}/confirm-death', [AdmissionController::class, 'confirmDeath']);

    // Convert outpatient to inpatient (admission/doctor/root)
    Route::post('/admissions/{id}/convert-to-inpatient', [AdmissionController::class, 'convertToInpatient']);

    // ==========================================
    // TREATMENT RECORDS (Medical History)
    // ==========================================

    // Treatment records for an admission
    Route::get('/admissions/{admissionId}/treatments', [TreatmentRecordController::class, 'index']);
    Route::post('/admissions/{admissionId}/treatments', [TreatmentRecordController::class, 'store']);
    Route::get('/admissions/{admissionId}/treatments/{recordId}', [TreatmentRecordController::class, 'show']);
    Route::put('/admissions/{admissionId}/treatments/{recordId}', [TreatmentRecordController::class, 'update']);
    Route::patch('/admissions/{admissionId}/treatments/{recordId}', [TreatmentRecordController::class, 'update']);
    // Route::delete('/admissions/{admissionId}/treatments/{recordId}', [TreatmentRecordController::class, 'destroy']); // DISABLED: Treatment record deletion not allowed for data integrity
});
