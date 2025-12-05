<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Admission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Patient reference
        'patient_id',
        'admission_type',
        
        // Staff assignment
        'doctor_id',
        'nurse_id',
        
        // Admission details
        'admission_number',
        'admission_date',
        'admission_time',
        'present_address',
        'admitted_for',
        'referred_by',
        'police_case',
        'service',
        'ward',
        'bed_number',
        'medical_officer',
        
        // Initial assessment
        'initial_diagnosis',
        'drug_allergy_noted',
        'remarks',
        
        // Discharge information
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
        
        // Death information
        'cause_of_death',
        'autopsy',
        'time_of_death',
        
        // Certification
        'certified_by',
        'approved_by',
        'attending_doctor_name',
        'attending_doctor_signature',
        
        // Status
        'status',
        // 'billing_status', // DISABLED: Free hospital - no billing required
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'discharge_date' => 'date',
            'follow_up_date' => 'date',
            'time_of_death' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate admission number on create
        static::creating(function ($admission) {
            if (empty($admission->admission_number)) {
                $admission->admission_number = self::generateAdmissionNumber();
            }
        });

        // CRITICAL: Protect data integrity on updates
        static::updating(function ($admission) {
            $originalStatus = $admission->getOriginal('status');
            $newStatus = $admission->status;
            $originalType = $admission->getOriginal('admission_type');
            $newType = $admission->admission_type;

            // =========================================================
            // RULE 1: Cannot change status to 'deceased' via update
            // Must use POST /admissions/{id}/confirm-death endpoint
            // =========================================================
            if ($admission->isDirty('status') && $newStatus === 'deceased' && $originalStatus !== 'deceased') {
                // Check if this is coming from confirmDeath endpoint (has required fields)
                $hasRequiredDeathFields = (
                    !empty($admission->cause_of_death) &&
                    !empty($admission->discharge_date) &&
                    $admission->discharge_status === 'dead'
                );
                
                if (!$hasRequiredDeathFields) {
                    throw new \Exception(
                        'Cannot set status to deceased via update. ' .
                        'Use POST /api/admissions/{id}/confirm-death endpoint instead.'
                    );
                }
            }

            // =========================================================
            // RULE 2: Cannot change status to 'discharged' via update
            // Must use POST /admissions/{id}/discharge endpoint
            // =========================================================
            if ($admission->isDirty('status') && $newStatus === 'discharged' && $originalStatus === 'admitted') {
                // Check if this is coming from discharge endpoint (has required fields)
                $hasRequiredDischargeFields = (
                    !empty($admission->discharge_type) &&
                    !empty($admission->discharge_status) &&
                    !empty($admission->discharge_date)
                );
                
                if (!$hasRequiredDischargeFields) {
                    throw new \Exception(
                        'Cannot set status to discharged via update. ' .
                        'Use POST /api/admissions/{id}/discharge endpoint instead.'
                    );
                }
            }

            // =========================================================
            // RULE 3: Cannot change admission_type via update
            // Must use POST /admissions/{id}/convert-to-inpatient endpoint
            // =========================================================
            if ($admission->isDirty('admission_type') && $originalType !== $newType) {
                // Only allow outpatient -> inpatient conversion via dedicated endpoint
                if ($originalType === 'outpatient' && $newType === 'inpatient') {
                    // Check if this is from convert endpoint (has ward)
                    if (empty($admission->ward)) {
                        throw new \Exception(
                            'Cannot change admission type via update. ' .
                            'Use POST /api/admissions/{id}/convert-to-inpatient endpoint instead.'
                        );
                    }
                } else {
                    // Inpatient -> Outpatient is NEVER allowed
                    throw new \Exception(
                        'Cannot change admission type from inpatient to outpatient. ' .
                        'This change is not permitted.'
                    );
                }
            }

            // =========================================================
            // RULE 4: Cannot reverse deceased status (death is FINAL)
            // =========================================================
            if ($admission->isDirty('status') && $originalStatus === 'deceased') {
                throw new \Exception(
                    'Cannot change status of deceased admission. ' .
                    'Death is a final status and cannot be reversed.'
                );
            }

            // =========================================================
            // RULE 5: Cannot modify most fields on deceased admissions
            // Death records should be immutable (legal/medical compliance)
            // =========================================================
            if ($originalStatus === 'deceased' && $admission->isDirty()) {
                // Only allow very limited administrative corrections
                $allowedChangesOnDeceased = ['remarks', 'updated_at'];
                $changedFields = array_keys($admission->getDirty());
                $unauthorizedChanges = array_diff($changedFields, $allowedChangesOnDeceased);
                
                if (!empty($unauthorizedChanges)) {
                    throw new \Exception(
                        'Cannot modify deceased admission records. Death records are immutable. ' .
                        'Attempted to change: ' . implode(', ', $unauthorizedChanges)
                    );
                }
            }

            // =========================================================
            // RULE 6: Limited changes allowed on discharged admissions
            // =========================================================
            if ($originalStatus === 'discharged' && $admission->isDirty()) {
                // Allow follow-up related changes and minor corrections
                $allowedChangesOnDischarged = [
                    'remarks', 'follow_up_instructions', 'follow_up_date',
                    'discharge_instructions', 'updated_at'
                ];
                $changedFields = array_keys($admission->getDirty());
                $unauthorizedChanges = array_diff($changedFields, $allowedChangesOnDischarged);
                
                if (!empty($unauthorizedChanges)) {
                    \Illuminate\Support\Facades\Log::warning('Attempt to modify discharged admission', [
                        'admission_id' => $admission->id,
                        'unauthorized_changes' => $unauthorizedChanges,
                    ]);
                    throw new \Exception(
                        'Limited updates allowed on discharged admissions. ' .
                        'Can only update: remarks, follow_up_instructions, follow_up_date, discharge_instructions. ' .
                        'Attempted to change: ' . implode(', ', $unauthorizedChanges)
                    );
                }
            }
        });
    }

    /**
     * Generate a unique admission number.
     */
    public static function generateAdmissionNumber(): string
    {
        $year = date('Y');
        $latestAdmission = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $latestAdmission ? ((int)substr($latestAdmission->admission_number, -6) + 1) : 1;
        
        return sprintf('ADM-%s-%06d', $year, $sequence);
    }

    /**
     * Get the patient for this admission.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor assigned to this admission.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the nurse assigned to this admission.
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * Get all treatment records for this admission.
     */
    public function treatmentRecords(): HasMany
    {
        return $this->hasMany(TreatmentRecord::class)->orderBy('treatment_date', 'desc');
    }

    /**
     * Calculate length of stay in days.
     */
    public function getLengthOfStayAttribute(): ?int
    {
        if (!$this->admission_date) {
            return null;
        }

        $endDate = $this->discharge_date ?? now();
        return $this->admission_date->diffInDays($endDate);
    }

    /**
     * Scope for active admissions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'admitted');
    }

    /**
     * Scope for discharged admissions.
     */
    public function scopeDischarged($query)
    {
        return $query->where('status', 'discharged');
    }

    /**
     * Scope for admissions by a specific doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for admissions with a specific nurse.
     */
    public function scopeForNurse($query, $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    /**
     * Get valid status values.
     */
    public static function statuses(): array
    {
        return ['admitted', 'discharged', 'deceased', 'transferred'];
    }

    /**
     * Get valid discharge types.
     */
    public static function dischargeTypes(): array
    {
        return ['normal', 'against_advice', 'absconded', 'transferred'];
    }

    /**
     * Get valid discharge statuses.
     */
    public static function dischargeStatuses(): array
    {
        return ['improved', 'unchanged', 'worse', 'dead'];
    }

    /**
     * Get valid billing statuses.
     * DISABLED: Free hospital - no billing required
     */
    // public static function billingStatuses(): array
    // {
    //     return ['pending', 'partial', 'paid', 'waived'];
    // }

    /**
     * Get valid admission types.
     */
    public static function admissionTypes(): array
    {
        return ['outpatient', 'inpatient'];
    }

    /**
     * Scope for outpatient visits.
     */
    public function scopeOutpatient($query)
    {
        return $query->where('admission_type', 'outpatient');
    }

    /**
     * Scope for inpatient admissions.
     */
    public function scopeInpatient($query)
    {
        return $query->where('admission_type', 'inpatient');
    }

    /**
     * Check if this is an outpatient visit.
     */
    public function isOutpatient(): bool
    {
        return $this->admission_type === 'outpatient';
    }

    /**
     * Check if this is an inpatient admission.
     */
    public function isInpatient(): bool
    {
        return $this->admission_type === 'inpatient';
    }

    /**
     * Convert outpatient visit to inpatient admission.
     */
    public function convertToInpatient(array $data): bool
    {
        if ($this->isInpatient()) {
            return false; // Already inpatient
        }

        $this->update(array_merge($data, [
            'admission_type' => 'inpatient',
            'status' => 'admitted',
        ]));

        return true;
    }
}

