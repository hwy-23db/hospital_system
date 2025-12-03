<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role = null)
    {
        // Make sure the user is logged in
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Check if $role is defined and user role matches
        if ($role && $user->role !== $role) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
