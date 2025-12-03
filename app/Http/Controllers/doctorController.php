<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a paginated list of doctors.
     */
    public function index()
    {
        // Fetch doctors and paginate
        $doctors = Doctor::latest()->paginate(10);
        return view('doctor.index', compact('doctors'));
    }

    /**
     * Show the form for creating a new doctor.
     */
    public function create()
    {
        return view('doctor.create');
    }

    /**
     * Store a newly created doctor in the database.
     */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'employee_number' => 'required|string|unique:doctors,employee_number',
        'nrc_number' => 'required|string|unique:doctors,nrc_number',
    ]);

        Doctor::create($request->all());

        return redirect()->route('doctor.index')
            ->with('success', 'Doctor registered successfully.');
    }

    /**
     * Show the form for editing an existing doctor.
     */
    public function edit(Doctor $doctor)
    {
        return view('doctor.edit', compact('doctor'));
    }

    /**
     * Update the specified doctor in the database.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'employee_number' => 'required|max:255|unique:doctors,employee_number,' . $doctor->id,
            'name' => 'required|string|max:255',
            'nrc_number' => 'required|string|max:255',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id,
            'specialization' => 'required|string|max:255',
        ]);

        $doctor->update($request->only([
            'employee_number',
            'name',
            'nrc_number',
            'email',
            'specialization'
        ]));

        return redirect()->route('doctor.index')
            ->with('success', 'Doctor updated successfully.');
    }

    /**
     * Remove the specified doctor from the database.
     */
    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()->route('doctor.index')
            ->with('success', 'Doctor deleted successfully.');
    }
}
