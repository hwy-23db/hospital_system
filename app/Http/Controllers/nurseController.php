<?php

namespace App\Http\Controllers;

use App\Models\nurse;
use Illuminate\Http\Request;

class nurseController extends Controller
{
    /**
     * Display a paginated list of nurses.
     */
    public function index()
    {
        // Fetch nurses and paginate
        $nurses = nurse::latest()->paginate(10);
        return view('nurse.index', compact('nurses'));
    }

    /**
     * Show the form for creating a new nurse.
     */
    public function create()
    {
        return view('nurse.create');
    }

    /**
     * Store a newly created nurse in the database.
     */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'employee_number' => 'required|string|unique:nurses,employee_number',
        'nrc_number' => 'required|string|unique:nurses,nrc_number',
    ]);

        nurse::create($request->all());

        return redirect()->route('nurse.index')
            ->with('success', 'nurse registered successfully.');
    }

    /**
     * Show the form for editing an existing nurse.
     */
    public function edit(nurse $nurse)
    {
        return view('nurse.edit', compact('nurse'));
    }

    /**
     * Update the specified nurse in the database.
     */
    public function update(Request $request, nurse $nurse)
    {
        $request->validate([
            'employee_number' => 'required|max:255|unique:nurses,employee_number,' . $nurse->id,
            'name' => 'required|string|max:255',
            'nrc_number' => 'required|string|max:255',
            'email' => 'required|email|unique:nurses,email,' . $nurse->id,
            'specialization' => 'required|string|max:255',
        ]);

        $nurse->update($request->only([
            'employee_number',
            'name',
            'nrc_number',
            'email',
            'specialization'
        ]));

        return redirect()->route('nurse.index')
            ->with('success', 'nurse updated successfully.');
    }

    /**
     * Remove the specified nurse from the database.
     */
    public function destroy(nurse $nurse)
    {
        $nurse->delete();

        return redirect()->route('nurse.index')
            ->with('success', 'nurse deleted successfully.');
    }
}
