<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('welcome'));

// ----------------------------------------------------
// ROOT USER ONLY 👑
// ----------------------------------------------------
Route::middleware(['auth', 'role:root_user'])->group(function () {
    // Users CRUD is exclusive to Root
    Route::resource('users', UserController::class);

    // Only Root can DELETE patients
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])
        ->name('patients.destroy');
});

// ----------------------------------------------------
// ALL AUTHENTICATED USERS (Shared Access) 🧑‍🤝‍🧑
// ----------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Dashboard & Profile (Standard for all)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // READ ACCESS (Index & Show are open to all authenticated users)
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->whereNumber('patient')
        ->name('patients.show');

    // WRITE ACCESS (Create, Store, Edit, Update)
    // These routes are open to all authenticated users,
    // but the PatientController will immediately check the user's role
    // (root_user OR receptionist) before execution.
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');

    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])
        ->whereNumber('patient')
        ->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])
        ->whereNumber('patient')
        ->name('patients.update');
});

require __DIR__.'/auth.php';
