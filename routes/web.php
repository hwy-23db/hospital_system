<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\NurseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/csrf-token', function() {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
});

//admin login
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
});

//doctor login
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/doctor/dashboard', [DoctorController::class, 'index'])->name('doctor.dashboard');
});

//nurse login
Route::middleware(['auth', 'role:nurse'])->group(function () {
    Route::get('/nurse/dashboard', [NurseController::class, 'index'])->name('nurse.dashboard');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});


require __DIR__.'/auth.php';
