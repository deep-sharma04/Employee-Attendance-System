<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
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
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['username']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Too many login attempts. Please wait {$seconds} seconds before trying again.",
            ])->onlyInput('username');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            // Verify active account flag
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'These credentials do not match our records.',
                ]);
            }

            // Verify active employee status
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
                    ]);
                }
            }

            return $this->redirectUserByRole($user);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'username' => 'These credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
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
            default => redirect()->route('login'),
        };
    }
}
