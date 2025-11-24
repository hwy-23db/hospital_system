<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Login endpoint - rate limiting is handled in LoginRequest class
// 5 attempts per email+IP combination with 60 second lockout
Route::post('/login', [AuthController::class, 'login']);

// Note: CSRF token endpoint is not needed for token-based API authentication
// For stateful SPA authentication, use Sanctum's built-in endpoint: GET /sanctum/csrf-cookie

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Get current authenticated user
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    });

    // Logout endpoint
    Route::post('/logout', [AuthController::class, 'logout']);

    // Registration endpoint - ONLY accessible by root_user
    // Root user can create other users (admission, nurse, doctor)
    // Note: Rate limiting not needed here since endpoint requires authentication + root_user role
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('root_user');

    // User management endpoints - ONLY accessible by root_user
    Route::middleware('root_user')->group(function () {
        // List all users
        Route::get('/users', [AuthController::class, 'index']);
    });
});
