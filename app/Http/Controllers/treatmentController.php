<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\Patient;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    /**
     * Store a new treatment for a patient.
     */
    public function store(Request $request, $patientId)
    {
        // Validate input
        $request->validate([
            'treatment_date' => 'required|date',
            'description' => 'required|string|max:255',
            'doctor' => 'nullable|string|max:100',
        ]);

        // Make sure patient exists
        $patient = Patient::findOrFail($patientId);

        // Create new treatment
        $patient->treatments()->create([
            'treatment_date' => $request->treatment_date,
            'description' => $request->description,
            'doctor' => $request->doctor,
        ]);

        return back()->with('success', 'Treatment added successfully.');
    }

    /**
     * Optional: show treatments for a patient (if needed).
     */
    public function index($patientId)
    {
        $patient = Patient::with('treatments')->findOrFail($patientId);
        return view('patients.treatments.index', compact('patient'));
    }

    /**
     * Delete a treatment.
     */
    public function destroy($treatmentId)
    {
        $treatment = Treatment::findOrFail($treatmentId);
        $treatment->delete();

        return back()->with('success', 'Treatment deleted successfully.');
    }
}
