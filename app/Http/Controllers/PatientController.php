<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of patients based on role
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $patients = Patient::with(['doctor', 'nurse'])->latest()->paginate(10);
        } elseif ($user->role === 'doctor') {
            $patients = Patient::with(['doctor', 'nurse'])
                ->where('doctor_id', $user->id)
                ->latest()
                ->paginate(10);
        } elseif ($user->role === 'nurse') {
            $patients = Patient::with(['doctor', 'nurse'])
                ->where('nurse_id', $user->id)
                ->latest()
                ->paginate(10);
        } else {
            $patients = collect();
        }

        return view('patients.index', compact('patients'));
    }

    /**
     * Show form to create a new patient (admin only)
     */
    public function create()
    {
        $this->authorizeRole('admin');

        $doctors = User::where('role', 'doctor')->get();
        $nurses  = User::where('role', 'nurse')->get();

        return view('patients.create', compact('doctors', 'nurses'));
    }

    /**
     * Store a new patient (admin only)
     */
    public function store(Request $request)
{
    $this->authorizeRole('admin');

    $validated = $this->validatePatient($request);

    // Create patient
    $patient = Patient::create($validated);

    // Store treatment record with doctor and nurse
    if ($request->filled('treatment_type') && $request->doctor_id && $request->nurse_id) {
        $patient->treatments()->create([
            'patient_id'     => $patient->id,
            'treatment_type' => $request->treatment_type,
            'doctor_id'      => $request->doctor_id,
            'nurse_id'       => $request->nurse_id,
        ]);
    }

    return redirect()->route('patients.index')->with('success', 'Patient registered successfully.');
}

    /**
     * Show a patient (role-based access)
     */
    public function show(Patient $patient)
    {
        $this->authorizePatientAccess($patient);

        $patient->load(['doctor', 'nurse', 'treatments']);

        return view('patients.show', compact('patient'));
    }

    /**
     * Show the edit form (role-based access)
     */
    public function edit(Patient $patient)
    {
        $this->authorizePatientAccess($patient);

        $doctors = User::where('role', 'doctor')->get();
        $nurses  = User::where('role', 'nurse')->get();
        $patient->load('treatments');

        return view('patients.edit', compact('patient', 'doctors', 'nurses'));
    }

    /**
     * Update a patient (role-based access)
     */
    public function update(Request $request, Patient $patient)
    {
        $this->authorizePatientAccess($patient);

        $validated = $this->validatePatient($request);
        $patient->update($validated);

        // Update existing treatments
        if ($request->has('treatments')) {
            foreach ($request->treatments as $id => $data) {
                $treatment = TreatmentRecord::find($id);
                if ($treatment) $treatment->update($data);
            }
        }

        // Add new treatments
        if ($request->has('new_treatments')) {
            foreach ($request->new_treatments as $data) {
                $patient->treatments()->create($data);
            }
        }

        return redirect()->route('patients.show', $patient->id)
                         ->with('success', 'Patient and treatments updated successfully.');
    }

    /**
     * Delete a patient (admin only)
     */
    public function destroy(Patient $patient)
    {
        $this->authorizeRole('admin');

        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
    }

    /**
     * Validate patient request
     */
    protected function validatePatient(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:users,id',
            'nurse_id' => 'nullable|exists:users,id',
            'sex' => 'nullable|string|max:10',
            'age' => 'nullable|integer|min:0|max:150',
            'dob' => 'nullable|date',
            'permanent_address' => 'nullable|string|max:500',
            'marital_status' => 'nullable|string|max:50',
            'ethnic_group' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'prev_admission_date' => 'nullable|date',
            'nearest_relative_name' => 'nullable|string|max:255',
            'relationship' => 'nullable|string|max:50',
            'referred_by' => 'nullable|string|max:255',
            'police_case' => 'nullable|string|max:255',
            'present_address' => 'nullable|string|max:500',
            'medical_officer' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'admission_date' => 'nullable|date',
            'admission_time' => 'nullable',
            'mother_name' => 'nullable|string|max:255',
            'discharge_date' => 'nullable|date',
            'discharge_time' => 'nullable',
            'admitted_for' => 'nullable|string|max:500',
            'drug_allergy' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
            'discharge_diagnosis' => 'nullable|string|max:500',
            'other_diagnosis' => 'nullable|string|max:500',
            'external_cause_of_injury' => 'nullable|string|max:500',
            'clinician_summary' => 'nullable|string|max:1000',
            'surgical_procedure' => 'nullable|string|max:500',
            'discharge_type' => 'nullable|string|max:255',
            'discharge_status' => 'nullable|string|max:255',
            'cause_of_death' => 'nullable|string|max:255',
            'treatment_record' => 'nullable|string|max:1000',
            'autopsy' => 'nullable|string|max:500',
            'certified_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_signature' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);
    }

    /**
     * Check if the current user can access the patient
     */
    protected function authorizePatientAccess(Patient $patient)
    {
        $user = auth()->user();

        if ($user->role === 'admin') return true;
        if ($user->role === 'doctor' && $patient->doctor_id === $user->id) return true;
        if ($user->role === 'nurse' && $patient->nurse_id === $user->id) return true;

        abort(403, 'Unauthorized');
    }

    /**
     * Check if the user has a specific role
     */
    protected function authorizeRole(string $role)
    {
        if (auth()->user()->role !== $role) {
            abort(403, 'Unauthorized');
        }
    }

}
