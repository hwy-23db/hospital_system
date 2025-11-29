<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

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
     * Update Profile Endpoint
     * Allows authenticated users to update their own profile information
     * Users can update name, email, and/or password
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $updatedFields = [];

        // Update name if provided
        if ($request->has('name')) {
            $user->name = $request->validated('name');
            $updatedFields[] = 'name';
        }

        // Update email if provided
        if ($request->has('email')) {
            $oldEmail = $user->email;
            $user->email = $request->validated('email');
            $updatedFields[] = 'email';

            // If email changed, reset email verification
            if ($oldEmail !== $user->email) {
                $user->email_verified_at = null;
            }
        }

        // Update password if provided
        if ($request->has('password')) {
            $user->password = $request->validated('password');
            $updatedFields[] = 'password';
        }

        // Only save if there are changes
        if (!empty($updatedFields)) {
            $user->save();

            // Log the profile update for audit trail
            Log::info('User profile updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated_fields' => $updatedFields,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);
        }

        // No fields to update
        return response()->json([
            'message' => 'No changes provided',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
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

    /**
     * Send Password Reset Link Endpoint
     * Sends a password reset link to the specified user's email
     * ONLY accessible by root_user
     */
    public function sendPasswordResetLink(ForgotPasswordRequest $request)
    {
        // Find the user by user_id or email
        $user = null;
        if ($request->has('user_id')) {
            $user = User::find($request->validated('user_id'));
        } else {
            $user = User::where('email', $request->validated('email'))->first();
        }

        // Security: Always return success message to prevent user enumeration
        // Don't reveal whether user exists or not
        if (!$user) {
            Log::warning('Password reset requested for non-existent user', [
                'requested_by' => $request->user()->id,
                'requested_email' => $request->validated('email') ?? null,
                'requested_user_id' => $request->validated('user_id') ?? null,
                'ip' => $request->ip(),
            ]);

            // Return generic success message to prevent user enumeration
            return response()->json([
                'message' => 'If the email address exists in our system, a password reset link has been sent.'
            ], 200);
        }

        // Prevent root user from resetting their own password through this endpoint
        // (They should use the standard forgot password flow)
        if ($user->role === 'root_user') {
            Log::warning('Attempt to send password reset link to root_user via admin endpoint blocked', [
                'attempted_by' => $request->user()->id,
                'target_user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Cannot send password reset link to root user through this endpoint.'
            ], 403);
        }

        // Send password reset link
        $status = Password::sendResetLink(
            ['email' => $user->email]
        );

        // Log the action for audit trail
        Log::info('Password reset link sent by root_user', [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'sent_by' => $request->user()->id,
            'sent_by_email' => $request->user()->email,
            'status' => $status,
            'ip' => $request->ip(),
        ]);

        // Always return generic success message to prevent information disclosure
        // Don't reveal whether email was actually sent or user details
        return response()->json([
            'message' => 'If the email address exists in our system, a password reset link has been sent.'
        ], 200);
    }

    /**
     * Delete User Endpoint
     * Deletes a user from the system
     * ONLY accessible by root_user
     */
    public function destroy(Request $request, $id)
    {
        // Validate that id is a valid integer
        if (!is_numeric($id) || (int)$id != $id) {
            return response()->json([
                'message' => 'Invalid user ID provided.'
            ], 400);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'The specified user does not exist.'
            ], 404);
        }

        // Prevent deleting root user
        if ($user->role === 'root_user') {
            Log::warning('Attempt to delete root_user blocked', [
                'attempted_by' => $request->user()->id,
                'target_user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Cannot delete root user. Root user cannot be removed from the system.'
            ], 403);
        }

        // Store user info for logging before deletion
        $deletedUserInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        // Delete the user
        $user->delete();

        // Log the deletion for audit trail
        Log::info('User deleted by root_user', [
            'deleted_user_id' => $deletedUserInfo['id'],
            'deleted_email' => $deletedUserInfo['email'],
            'deleted_role' => $deletedUserInfo['role'],
            'deleted_by' => $request->user()->id,
            'deleted_by_email' => $request->user()->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'User deleted successfully',
            'deleted_user' => $deletedUserInfo
        ], 200);
    }
}
