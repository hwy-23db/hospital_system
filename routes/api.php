<?php

use App\Http\Controllers\Api\AuthController;

// CSRF Token
Route::get('/csrf-token', [AuthController::class, 'csrfToken']);

// Register
Route::post('/register', [AuthController::class, 'register']);

// Login
Route::post('/login', [AuthController::class, 'login']);
