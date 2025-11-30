<?php

namespace App\Http\Controllers;

use App\Models\Nurse;
use Illuminate\Http\Request;

class nurseController extends Controller
{
    public function index()
    {
        $nurse = nurse::latest()->paginate(10);
        return view('nurse.index', compact('nurse'));
    }

    public function create()
    {
        return view('nurse.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        nurse::create($request->all());

        return redirect()->route('nurse.index')
            ->with('success', 'User registered successfully.');
    }

    public function edit(nurse $nurse)
    {
        return view('nurse.edit', compact('nurse'));
    }

    public function update(Request $request, nurse $nurse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $nurse->update($request->all());

        return redirect()->route('nurse.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(nurse $nurse)
    {
        $nurse->delete();

        return redirect()->route('nurse.index')
            ->with('success', 'User deleted successfully.');
    }
}
