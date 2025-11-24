<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::middleware('auth:sanctum')->group(function () {
    //get current user
    Route::get('/user', function(Request $request) {
        return $request->user();
    });
});

 // Logout
    Route::post('/logout', function(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });

Route::post('/login', function(Request $request){
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    // create token
    $token = $user->createToken('frontend-token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
});
