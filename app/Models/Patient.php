<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        // 'nrc_number',
        'sex',
        'age',
        'dob',
        'permanent_address',
        'marital_status',
        'ethnic_group',
        'religion',
        'occupation',
        'prev_admission_date',
        'nearest_relative_name',
        'relationship',
        'referred_by',
        'police_case',
        'present_address',
        'medical_officer',
        'service',
        'ward',
        'father_name',
        'admission_date',
        'admission_time',
        'mother_name',
        'discharge_date',
        'discharge_time',
        'admitted_for',
        'drug_allergy',
        'remarks',
        'discharge_diagnosis',
        'other_diagnosis',
        'external_cause_of_injury',
        'clinician_summary',
        'surgical_procedure',
        'discharge_type',
        'discharge_status',
        'cause_of_death',
        'treatment_record',
        'autopsy',
        'certified_by',
        'approved_by',
        'doctor_name',
        'doctor_signature',
        'contact_phone',
        'doctor_id',
        'nurse_id',
    ];

    /**
     * Relationship: Patient belongs to Doctor
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Relationship: Patient belongs to Nurse
     */
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    public function treatments()
{
    return $this->hasMany(TreatmentRecord::class);
}

}
