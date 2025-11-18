<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Role Middleware
 *
 * Verifies that the authenticated user has one of the required roles.
 * Usage: ->middleware('role:admin,organizer')
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$roles  Required roles (comma-separated)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = $request->user();

        // Check if user has one of the required roles
        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Insufficient permissions',
            ], 403);
        }

        return $next($request);
    }
}
