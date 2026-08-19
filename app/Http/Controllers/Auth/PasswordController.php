<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\ForgotPasswordMail;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Show the change password form.
     */
    public function showChangeForm(): View
    {
        return view('auth.change-password');
    }

    /**
     * Update the user password after verifying current password.
     */
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        $this->auditLogger->log(
            action: 'auth.password_changed',
            targetType: 'App\Models\User',
            targetId: $user->id,
            description: "User {$user->username} changed their password."
        );

        return back()->with('success', 'Your password has been successfully updated.');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle password reset request for all user roles.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $input = trim((string) ($validated['username'] ?? ''));

        // Multi-criteria lookup supporting Username, Email, Employee Code/ID, and Client Code
        $user = User::query()
            ->with(['employee', 'clientUser.client'])
            ->where(function ($query) use ($input) {
                $query->where('username', $input)
                    ->orWhere('email', $input)
                    ->orWhereHas('employee', function ($q) use ($input) {
                        $q->where('employee_code', $input)
                            ->orWhere('email', $input);
                    })
                    ->orWhereHas('clientUser.client', function ($q) use ($input) {
                        $q->where('company_code', $input)
                            ->orWhere('email', $input);
                    });
            })
            ->first();

        if ($user && $user->is_active) {
            // Resolve recipient email address (fallback to employee or client contact email if user email is empty)
            $recipientEmail = $user->email;
            if (empty($recipientEmail) && $user->employee && !empty($user->employee->email)) {
                $recipientEmail = $user->employee->email;
                $user->email = $recipientEmail;
                $user->save();
            } elseif (empty($recipientEmail) && $user->clientUser && $user->clientUser->client && !empty($user->clientUser->client->email)) {
                $recipientEmail = $user->clientUser->client->email;
                $user->email = $recipientEmail;
                $user->save();
            }

            if (!empty($recipientEmail)) {
                $token = Str::random(64);
                $expireMinutes = (int) config('auth.passwords.users.expire', 60);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $recipientEmail],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );

                $this->auditLogger->log(
                    action: 'auth.password_reset_requested',
                    targetType: 'App\Models\User',
                    targetId: $user->id,
                    description: "Password reset token generated for user {$user->username} ({$recipientEmail})."
                );

                $resetUrl = route('password.reset', ['token' => $token, 'email' => $recipientEmail]);

                // Dynamically apply configured SMTP settings before dispatch
                try {
                    app(\App\Services\Settings\SettingsService::class)->applyMailConfiguration();
                    Mail::to($recipientEmail)->send(new ForgotPasswordMail($user, $resetUrl, $expireMinutes));
                } catch (\Throwable $e) {
                    Log::warning("Failed to dispatch password reset email to {$recipientEmail}: " . $e->getMessage());
                }

                // In local/testing development environment, flash reset URL for convenient testing
                if (config('app.env') !== 'production' || config('app.debug')) {
                    session()->flash('dev_reset_url', $resetUrl);
                }

                session()->flash('reset_sent_to', $recipientEmail);
            }
        }

        return back()->with('status', 'If your account exists and is eligible for password reset, a secure link has been sent to your registered email address.');
    }

    /**
     * Show the reset password form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset password using verified token.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = trim((string) $validated['email']);

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        if (
            !$record ||
            !Hash::check($validated['token'], $record->token) ||
            Carbon::parse($record->created_at)->addMinutes($expireMinutes)->isPast()
        ) {
            return back()->withErrors(['email' => 'This password reset token is invalid or has expired. Please request a new link.']);
        }

        $user = User::where('email', $email)
            ->orWhereHas('employee', fn($q) => $q->where('email', $email))
            ->orWhereHas('clientUser.client', fn($q) => $q->where('email', $email))
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Unable to locate an account associated with this email address.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $this->auditLogger->log(
            action: 'auth.password_reset_completed',
            targetType: 'App\Models\User',
            targetId: $user->id,
            description: "User {$user->username} completed password reset."
        );

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. You may now sign in with your new credentials.');
    }
}
