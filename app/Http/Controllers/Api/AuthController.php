<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register Endpoint
     * Creates a new user account with validated credentials
     * ONLY accessible by root_user - root_user can create admission, nurse, or doctor roles
     */
    public function register(RegisterRequest $request)
    {
        $role = $request->validated('role');

        // Additional security check: Prevent creating root_user through API
        // Root user is only created via seeder
        if ($role === 'root_user') {
            Log::warning('Attempt to create root_user via API blocked', [
                'attempted_by' => $request->user()->id,
                'email' => $request->user()->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Root user cannot be created via API. Root user is only created through database seeding.'
            ], 403);
        }

        // Create user with validated data
        // Password will be automatically hashed by User model's 'hashed' cast
        $user = User::create([
            'name'     => $request->validated('name'),
            'email'    => $request->validated('email'),
            'password' => $request->validated('password'),
            'role'     => $role,
        ]);

        // Log registration for audit trail
        Log::info('User registered by root_user', [
            'created_user_id' => $user->id,
            'created_email' => $user->email,
            'created_role' => $user->role,
            'registered_by' => $request->user()->id,
            'registered_by_email' => $request->user()->email,
            'ip' => $request->ip(),
        ]);

        // Return sanitized user data (exclude sensitive fields)
        return response()->json([
            'message' => 'User registered successfully',
            'user'    => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]
        ], 201);
    }

    /**
     * Login Endpoint
     * Authenticates user and returns access token
     */
    public function login(LoginRequest $request)
    {
        // Authenticate user (includes rate limiting check)
        $request->authenticate();

        // Get the authenticated user (already validated in LoginRequest)
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        // Create token with expiration (24 hours)
        $expiresAt = now()->addHours(24);
        $token = $user->createToken('api_token', ['*'], $expiresAt);

        // Return sanitized user data
        return response()->json([
            'message' => 'Login successful',
            'token'   => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user'    => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Logout Endpoint
     * Revokes the current access token
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        $tokenId = $token->id;

        // Delete the current access token
        $token->delete();

        // Log logout for audit trail
        Log::info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
            'token_id' => $tokenId,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * List Users Endpoint
     * Returns a list of all users in the system
     * ONLY accessible by root_user
     */
    public function index(Request $request)
    {
        // Get all users, excluding sensitive fields
        $users = User::select('id', 'name', 'email', 'role', 'email_verified_at', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Log user list access for audit trail
        Log::info('User list accessed by root_user', [
            'accessed_by' => $request->user()->id,
            'accessed_by_email' => $request->user()->email,
            'total_users' => $users->count(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Users retrieved successfully',
            'total' => $users->count(),
            'users' => $users,
        ]);
    }
}
