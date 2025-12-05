<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'admission_id',
        'patient_id',
        'doctor_id',
        'nurse_id',
        'treatment_type',
        'treatment_name',
        'description',
        'notes',
        'medications',
        'dosage',
        'treatment_date',
        'treatment_time',
        'results',
        'findings',
        'outcome',
        'pre_procedure_notes',
        'post_procedure_notes',
        'complications',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'treatment_date' => 'date',
        ];
    }

    /**
     * Get the valid treatment types.
     */
    public static function treatmentTypes(): array
    {
        return [
            'surgery',
            'radiotherapy',
            'chemotherapy',
            'targeted_therapy',
            'hormone_therapy',
            'immunotherapy',
            'intervention_therapy',
            'medication',
            'physical_therapy',
            'supportive_care',
            'diagnostic',
            'consultation',
            'procedure',
            'other',
        ];
    }

    /**
     * Get the valid outcomes.
     */
    public static function outcomes(): array
    {
        return [
            'pending',
            'successful',
            'partial',
            'unsuccessful',
            'ongoing',
            'completed',
        ];
    }

    /**
     * Get the admission this treatment belongs to.
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the patient this treatment belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor who performed/ordered this treatment.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the nurse who assisted with this treatment.
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * Scope a query to only include treatments by a specific doctor.
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope a query to filter by treatment type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('treatment_type', $type);
    }

    /**
     * Scope a query to filter by admission.
     */
    public function scopeForAdmission($query, $admissionId)
    {
        return $query->where('admission_id', $admissionId);
    }
}
