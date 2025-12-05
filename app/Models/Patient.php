<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Only permanent demographic data - admission-specific data is in admissions table.
     */
    protected $fillable = [
        // Basic identification
        'name',
        'nrc_number',
        'sex',
        'age',
        'dob',
        'contact_phone',
        
        // Permanent address
        'permanent_address',
        
        // Personal details
        'marital_status',
        'ethnic_group',
        'religion',
        'occupation',
        'father_name',
        'mother_name',
        
        // Emergency contact
        'nearest_relative_name',
        'nearest_relative_phone',
        'relationship',
        
        // Medical info (permanent)
        'blood_type',
        'known_allergies',
        'chronic_conditions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get all admissions for this patient.
     */
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class)->orderBy('admission_date', 'desc');
    }

    /**
     * Get all treatment records for this patient (across all admissions).
     */
    public function treatmentRecords(): HasMany
    {
        return $this->hasMany(TreatmentRecord::class)->orderBy('treatment_date', 'desc');
    }

    /**
     * Get the current/active admission if any.
     */
    public function currentAdmission()
    {
        return $this->admissions()->where('status', 'admitted')->first();
    }

    /**
     * Check if patient is currently admitted.
     */
    public function isAdmitted(): bool
    {
        return $this->admissions()->where('status', 'admitted')->exists();
    }

    /**
     * Check if patient is deceased.
     * Once a death is confirmed in any admission, patient cannot be readmitted.
     */
    public function isDeceased(): bool
    {
        return $this->admissions()->where('status', 'deceased')->exists();
    }

    /**
     * Get the death record if patient is deceased.
     */
    public function getDeathRecord()
    {
        return $this->admissions()->where('status', 'deceased')->first();
    }

    /**
     * Get total number of admissions.
     */
    public function getTotalAdmissionsAttribute(): int
    {
        return $this->admissions()->count();
    }

    /**
     * Search patients by name, NRC, or phone.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('nrc_number', 'like', "%{$search}%")
              ->orWhere('contact_phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for patients with active admissions.
     */
    public function scopeCurrentlyAdmitted($query)
    {
        return $query->whereHas('admissions', function ($q) {
            $q->where('status', 'admitted');
        });
    }

    /**
     * Get blood type options.
     */
    public static function bloodTypes(): array
    {
        return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    }
}
