<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Nurse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit'); // create this blade file
    }

    public function update(Request $request)
    {
        $request->user()->update($request->only('name', 'email'));
        return redirect()->route('profile.edit')->with('success', 'Profile updated!');
    }
}

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:root_user,receptionist,doctor,nurse',
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Create role-specific entry
        if ($user->role === 'doctor') {
            Doctor::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } elseif ($user->role === 'nurse') {
            Nurse::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        // Trigger registered event
        event(new Registered($user));

        // Log the user in
        Auth::login($user);

         return match ($user->role) {
            'root_user' =>redirect()->route('dashboard'),
            'receptionist' => redirect()->route('dashboard'),
            'doctor' => redirect()->route('dashboard'),
            'nurse' => redirect()->route('dashboard'),
            default => redirect('/'), // fallback
        };
    }
}
