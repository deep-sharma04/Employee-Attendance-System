<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle login authentication with rate limiting and secure session regeneration.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('username', 'password');
        $selectedRole = $request->input('role');
        $remember = $request->boolean('remember');
        $throttleKey = Str::transliterate(Str::lower($credentials['username']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Too many login attempts. Please wait {$seconds} seconds before trying again.",
            ])->onlyInput('username', 'role');
        }

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            // Verify the selected role matches the user's actual role
            $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
            if ($userRole !== $selectedRole) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'role' => 'The selected role does not match your account. Please select the correct role.',
                ])->onlyInput('username', 'role');
            }

            // Verify active user flag (T027 / T034)
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'These credentials do not match our records.',
                ])->onlyInput('username', 'role');
            }

            // Verify active linked employee status (T027 / T034)
            if ($user->employee && isset($user->employee->status)) {
                $status = $user->employee->status instanceof EmployeeStatus
                    ? $user->employee->status->value
                    : (string) $user->employee->status;

                if ($status !== EmployeeStatus::ACTIVE->value) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'username' => 'These credentials do not match our records.',
                    ])->onlyInput('username', 'role');
                }
            }

            // Update last login metrics
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            // Audit login
            $this->auditLogger->log(
                action: 'auth.login',
                targetType: 'App\Models\User',
                targetId: $user->id,
                description: "User {$user->username} authenticated successfully as {$selectedRole}."
            );

            return $this->redirectUserByRole($user);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'username' => 'These credentials do not match our records.',
        ])->onlyInput('username', 'role');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogger->log(
                action: 'auth.logout',
                targetType: 'App\Models\User',
                targetId: $user->id,
                description: "User {$user->username} logged out."
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been securely logged out.');
    }

    /**
     * Redirect user to their corresponding role dashboard.
     */
    protected function redirectUserByRole($user): RedirectResponse
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        return match ($role) {
            UserRole::SUPER_ADMIN->value => redirect()->intended(route('super-admin.dashboard')),
            UserRole::HR_ADMIN->value => redirect()->intended(route('hr-admin.dashboard')),
            UserRole::EMPLOYEE->value => redirect()->intended(route('employee.dashboard')),
            UserRole::MANAGER->value => redirect()->intended(route('manager.dashboard')),
            UserRole::TEAM_LEAD->value => redirect()->intended(route('team-lead.dashboard')),
            UserRole::CLIENT->value => redirect()->intended(route('client-portal.dashboard')),
            default => redirect()->route('login'),
        };
    }
}
