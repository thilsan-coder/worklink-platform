<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access.',
            ], 401);
        }

        // Handle Admin role specifically if authenticated via admin guard
        if (in_array('admin', $roles) && $user instanceof \App\Models\AdminUser) {
            return $next($request);
        }

        if (in_array('admin', $roles) && ! ($user instanceof \App\Models\AdminUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Administrative privileges required.',
            ], 403);
        }

        // For regular users (customers / workers)
        if ($user instanceof \App\Models\User) {
            if ($user->role === 'both' || in_array($user->role, $roles)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden. Insufficient role permissions.',
        ], 403);
    }
}
