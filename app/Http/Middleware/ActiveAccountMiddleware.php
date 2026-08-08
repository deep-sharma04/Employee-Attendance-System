<?php

namespace App\Http\Middleware;

use App\Enums\EmployeeStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveAccountMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Check direct user active flag if present
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login' => 'Your account is deactivated. Please contact your system administrator.',
                ]);
            }

            // Check linked employee status if present
            if ($user->employee && isset($user->employee->status)) {
                $status = $user->employee->status instanceof EmployeeStatus
                    ? $user->employee->status->value
                    : (string) $user->employee->status;

                if ($status !== EmployeeStatus::ACTIVE->value) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'login' => 'Your employee profile is not active. Please contact HR administration.',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
