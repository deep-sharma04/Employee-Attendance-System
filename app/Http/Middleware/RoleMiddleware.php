<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
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

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Check if user's role is in the allowed list
        $allowed = false;
        foreach ($roles as $roleGroup) {
            $splitRoles = explode(',', $roleGroup);
            foreach ($splitRoles as $role) {
                if (trim($role) === $userRole) {
                    $allowed = true;
                    break 2;
                }
            }
        }

        if (!$allowed) {
            abort(403, 'Unauthorized action. Your user role (' . ($user->role?->label() ?? $userRole) . ') cannot access this resource.');
        }

        return $next($request);
    }
}
