<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RootUserMiddleware
{
    /**
     * Handle an incoming request.
     * Ensures only root_user role can access the route
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user has root_user role
        if ($user->role !== 'root_user') {
            return response()->json([
                'message' => 'Unauthorized. Only root user can access this endpoint.'
            ], 403);
        }

        return $next($request);
    }
}
