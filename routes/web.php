<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\TreatmentRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('welcome'));


// ----------------------------------------------------
// ADMIN ONLY
// ----------------------------------------------------
Route::middleware(['auth', 'role:admin'])->group(function () {

    // Users CRUD
    Route::resource('users', UserController::class);

    // Doctors CRUD
    Route::resource('doctor', DoctorController::class);

    // Nurses CRUD
    Route::resource('nurse', NurseController::class);

    // Admin: Full Patient CRUD (except index + show)
    Route::resource('patients', PatientController::class)
        ->except(['index', 'show'])
        ->where(['patient' => '[0-9]+']);
});


// ----------------------------------------------------
// DOCTOR ONLY
// ----------------------------------------------------
Route::middleware(['auth', 'role:doctor'])->group(function () {

    // Doctor patient list
    Route::get('/patients', [PatientController::class, 'myPatients'])
        ->name('patients.index');

    // Doctor: View patient
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->whereNumber('patient')
        ->name('patients.show');

    // // Doctor: Edit patient
    // Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])
    //     ->whereNumber('patient')
    //     ->name('patients.edit');

    // Doctor: Update patient
    Route::put('/patients/{patient}', [PatientController::class, 'update'])
        ->whereNumber('patient')
        ->name('patients.update');
});


// ----------------------------------------------------
// NURSE ONLY
// ----------------------------------------------------
Route::middleware(['auth', 'role:nurse'])->group(function () {

    // Nurse patient list
    Route::get('/patients', [PatientController::class, 'myPatients'])
        ->name('patients.index');

    // Nurse: View
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->whereNumber('patient')
        ->name('patients.show');

    // Nurse: Edit
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])
        ->whereNumber('patient')
        ->name('patients.edit');

    // Nurse: Update
    Route::put('/patients/{patient}', [PatientController::class, 'update'])
        ->whereNumber('patient')
        ->name('patients.update');
});


// ----------------------------------------------------
// ALL AUTHENTICATED USERS
// ----------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    // Shared read-only patient access
    Route::resource('patients', PatientController::class)
        ->only(['index', 'show'])
        ->where(['patient' => '[0-9]+']);
});


require __DIR__.'/auth.php';
