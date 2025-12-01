
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\NurseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('welcome'));

// ----------------------------------------------------
// ADMIN-ONLY ROUTES
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
// ALL AUTHENTICATED USERS
// ----------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    // Patients: index & show (read-only)
    Route::get('/patients', [PatientController::class, 'index'])
        ->name('patients.index');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->whereNumber('patient')
        ->name('patients.show');

    // Patients: edit & update (role-based control inside controller)
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])
        ->whereNumber('patient')
        ->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])
        ->whereNumber('patient')
        ->name('patients.update');
});

require __DIR__.'/auth.php';
