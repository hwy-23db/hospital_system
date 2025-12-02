<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\TreatmentRecord;
use Illuminate\Http\Request;

class TreatmentRecordController extends Controller
{
    public function store(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'nurse_id' => 'nullable|exists:users,id',
            'treatment_type' => 'required|in:surgery,radiotherapy,chemotherapy,targeted_therapy,hormone_therapy,immunotherapy,intervention_therapy,other,supportive_care',
            'notes' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($patientId);

        TreatmentRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $request->doctor_id,
            'nurse_id' => $request->nurse_id,
            'treatment_type' => $request->treatment_type,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Treatment record added!');
    }
}
