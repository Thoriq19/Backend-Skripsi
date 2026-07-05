<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has one of the allowed roles.
     * Usage in routes: ->middleware('role:owner') or ->middleware('role:owner|pengelola_kos')
     *
     * @param  string  $roles  Pipe-separated list of allowed roles (e.g., 'owner|pengelola_kos')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        $allowedRoles = explode('|', $roles);

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required role to access this resource.',
                'data'    => null,
                'errors'  => [
                    'role' => 'Required role: ' . implode(' or ', $allowedRoles) . '. Your role: ' . $user->role,
                ],
            ], 403);
        }

        return $next($request);
    }
}
