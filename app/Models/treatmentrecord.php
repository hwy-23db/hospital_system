<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'nurse_id',
        'treatment_type',
        'details',
        'date',
    ];

    // Link back to patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
