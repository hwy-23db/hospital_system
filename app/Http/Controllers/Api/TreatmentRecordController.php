<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTreatmentRecordRequest;
use App\Http\Requests\Api\UpdateTreatmentRecordRequest;
use App\Models\Admission;
use App\Models\TreatmentRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TreatmentRecordController extends Controller
{
    /**
     * List all treatment records for an admission.
     * Access based on role and assignment.
     */
    public function index(Request $request, $admissionId): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::with('patient:id,name')->find($admissionId);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Check access
        if (!$this->canAccessAdmission($user, $admission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this admission\'s treatment records.'
            ], 403);
        }

        $records = TreatmentRecord::where('admission_id', $admissionId)
            ->with(['doctor:id,name,email', 'nurse:id,name,email'])
            ->orderBy('treatment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Treatment records accessed', [
            'admission_id' => $admissionId,
            'accessed_by' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Treatment records retrieved successfully',
            'admission_id' => $admissionId,
            'admission_number' => $admission->admission_number,
            'patient_id' => $admission->patient_id,
            'patient_name' => $admission->patient->name,
            'total' => $records->count(),
            'data' => $records,
        ]);
    }

    /**
     * Store a new treatment record.
     * Only doctors can create treatment records for their assigned admissions.
     */
    public function store(StoreTreatmentRecordRequest $request, $admissionId): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::find($admissionId);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Only doctors and root can create treatment records
        if (!in_array($user->role, ['root_user', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors can create treatment records.'
            ], 403);
        }

        // Doctors can only add records for their assigned admissions
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only add treatment records for admissions assigned to you.'
            ], 403);
        }

        // Check if admission is still active
        if ($admission->status !== 'admitted') {
            return response()->json([
                'message' => 'Cannot add treatment records to a closed admission. Status: ' . $admission->status
            ], 400);
        }

        $data = $request->validated();
        $data['admission_id'] = $admissionId;
        $data['patient_id'] = $admission->patient_id;

        // Auto-set doctor_id to current user if not specified and user is a doctor
        if (!isset($data['doctor_id']) && $user->role === 'doctor') {
            $data['doctor_id'] = $user->id;
        }

        // Auto-set nurse_id from admission's assigned nurse if not specified
        if (!isset($data['nurse_id']) && $admission->nurse_id) {
            $data['nurse_id'] = $admission->nurse_id;
        }

        // Auto-set treatment date if not provided
        if (!isset($data['treatment_date'])) {
            $data['treatment_date'] = now()->toDateString();
        }

        // Handle file uploads
        $attachments = $this->handleFileUploads($request);
        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        $record = TreatmentRecord::create($data);

        Log::info('Treatment record created', [
            'record_id' => $record->id,
            'admission_id' => $admissionId,
            'patient_id' => $admission->patient_id,
            'treatment_type' => $record->treatment_type,
            'created_by' => $user->id,
            'ip' => $request->ip(),
        ]);

        $recordData = $record->load([
            'doctor:id,name,email',
            'nurse:id,name,email',
            'admission:id,admission_number',
            'patient:id,name'
        ])->toArray();

        $recordData['attachment_urls'] = $record->getAttachmentUrls();

        return response()->json([
            'message' => 'Treatment record created successfully',
            'data' => $recordData,
        ], 201);
    }

    /**
     * Show a specific treatment record.
     */
    public function show(Request $request, $admissionId, $recordId): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::find($admissionId);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Check access
        if (!$this->canAccessAdmission($user, $admission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this admission\'s treatment records.'
            ], 403);
        }

        $record = TreatmentRecord::where('admission_id', $admissionId)
            ->where('id', $recordId)
            ->with(['doctor:id,name,email', 'nurse:id,name,email'])
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Treatment record not found.'
            ], 404);
        }

        // Add attachment URLs to the response
        $recordData = $record->toArray();
        $recordData['attachment_urls'] = $record->getAttachmentUrls();

        return response()->json([
            'message' => 'Treatment record retrieved successfully',
            'data' => $recordData,
        ]);
    }

    /**
     * Update a treatment record.
     * Only doctors can update treatment records for their assigned admissions.
     */
    public function update(UpdateTreatmentRecordRequest $request, $admissionId, $recordId): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::find($admissionId);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Only doctors and root can update treatment records
        if (!in_array($user->role, ['root_user', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors can update treatment records.'
            ], 403);
        }

        // Doctors can only update records for their assigned admissions
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update treatment records for admissions assigned to you.'
            ], 403);
        }

        $record = TreatmentRecord::where('admission_id', $admissionId)
            ->where('id', $recordId)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Treatment record not found.'
            ], 404);
        }

        $data = $request->validated();

        // Handle file uploads - merge with existing attachments
        $newAttachments = $this->handleFileUploads($request);
        if (!empty($newAttachments)) {
            $existingAttachments = $record->attachments ?? [];
            $data['attachments'] = array_merge($existingAttachments, $newAttachments);
        }

        $record->update($data);

        Log::info('Treatment record updated', [
            'record_id' => $record->id,
            'admission_id' => $admissionId,
            'updated_by' => $user->id,
            'ip' => $request->ip(),
        ]);

        $recordData = $record->fresh()->load(['doctor:id,name,email', 'nurse:id,name,email'])->toArray();
        $recordData['attachment_urls'] = $record->getAttachmentUrls();

        return response()->json([
            'message' => 'Treatment record updated successfully',
            'data' => $recordData,
        ]);
    }

    /**
     * Delete a treatment record.
     * Only root_user can delete treatment records.
     *
     * COMMENTED OUT: Treatment record deletion is disabled for data integrity.
     * Medical treatment records must be retained for legal and medical compliance.
     */
    // public function destroy(Request $request, $admissionId, $recordId): JsonResponse
    // {
    //     $user = $request->user();

    //     if ($user->role !== 'root_user') {
    //         return response()->json([
    //             'message' => 'Unauthorized. Only root user can delete treatment records.'
    //         ], 403);
    //     }

    //     $record = TreatmentRecord::where('admission_id', $admissionId)
    //         ->where('id', $recordId)
    //         ->first();

    //     if (!$record) {
    //         return response()->json([
    //             'message' => 'Treatment record not found.'
    //         ], 404);
    //     }

    //     $recordInfo = [
    //         'id' => $record->id,
    //         'treatment_type' => $record->treatment_type,
    //         'admission_id' => $admissionId,
    //         'patient_id' => $record->patient_id,
    //     ];

    //     $record->delete();

    //     Log::info('Treatment record deleted', [
    //         'record_id' => $recordInfo['id'],
    //         'admission_id' => $admissionId,
    //         'deleted_by' => $user->id,
    //         'ip' => $request->ip(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Treatment record deleted successfully',
    //         'deleted_record' => $recordInfo,
    //     ]);
    // }

    /**
     * Get treatment types for dropdowns.
     */
    public function getTreatmentTypes(): JsonResponse
    {
        return response()->json([
            'message' => 'Treatment types retrieved successfully',
            'data' => TreatmentRecord::treatmentTypes(),
        ]);
    }

    /**
     * Get outcome options for dropdowns.
     */
    public function getOutcomes(): JsonResponse
    {
        return response()->json([
            'message' => 'Outcomes retrieved successfully',
            'data' => TreatmentRecord::outcomes(),
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
     * Remove a specific attachment from a treatment record.
     */
    public function removeAttachment(Request $request, $admissionId, $recordId, $filename): JsonResponse
    {
        $user = $request->user();
        $admission = Admission::find($admissionId);

        if (!$admission) {
            return response()->json([
                'message' => 'Admission not found.'
            ], 404);
        }

        // Only doctors and root can remove attachments
        if (!in_array($user->role, ['root_user', 'doctor'])) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors can remove attachments.'
            ], 403);
        }

        // Doctors can only modify records for their assigned admissions
        if ($user->role === 'doctor' && $admission->doctor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only modify treatment records for admissions assigned to you.'
            ], 403);
        }

        $record = TreatmentRecord::where('admission_id', $admissionId)
            ->where('id', $recordId)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Treatment record not found.'
            ], 404);
        }

        // Find and remove the attachment
        $attachments = $record->attachments ?? [];
        $attachmentToRemove = null;

        foreach ($attachments as $key => $attachment) {
            if (($attachment['filename'] ?? '') === $filename) {
                $attachmentToRemove = $attachment;
                unset($attachments[$key]);
                break;
            }
        }

        if (!$attachmentToRemove) {
            return response()->json([
                'message' => 'Attachment not found.'
            ], 404);
        }

        // Delete file from storage
        if (isset($attachmentToRemove['path']) && Storage::disk('public')->exists($attachmentToRemove['path'])) {
            Storage::disk('public')->delete($attachmentToRemove['path']);
        }

        // Update record
        $record->update(['attachments' => array_values($attachments)]);

        Log::info('Treatment record attachment removed', [
            'record_id' => $record->id,
            'filename' => $filename,
            'removed_by' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Attachment removed successfully',
            'data' => $record->fresh()->load(['doctor:id,name,email', 'nurse:id,name,email']),
        ]);
    }

    /**
     * Handle file uploads for treatment record attachments.
     */
    private function handleFileUploads(Request $request): array
    {
        $attachments = [];

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');

            foreach ($files as $file) {
                if ($file->isValid()) {
                    // Generate unique filename
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9\-_.]/', '', $originalName);

                    // Store file in treatment-attachments directory
                    $path = $file->storeAs(TreatmentRecord::getStoragePath(), $filename, 'public');

                    if ($path) {
                        $attachments[] = [
                            'filename' => $originalName,
                            'path' => $path,
                            'size' => $file->getSize(),
                            'uploaded_at' => now()->toISOString(),
                        ];
                    }
                }
            }
        }

        return $attachments;
    }
}
