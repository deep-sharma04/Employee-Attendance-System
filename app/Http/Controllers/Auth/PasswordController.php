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
     * Handle password reset request.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])
            ->orWhere('email', $validated['username'])
            ->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $this->auditLogger->log(
                action: 'auth.password_reset_requested',
                targetType: 'App\Models\User',
                targetId: $user->id,
                description: "Password reset token generated for user {$user->username}."
            );

            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

            // Dispatch password reset email via Mail service
            if (!empty($user->email)) {
                try {
                    $expireMinutes = (int) config('auth.passwords.users.expire', 60);
                    Mail::to($user->email)->send(new ForgotPasswordMail($user, $resetUrl, $expireMinutes));
                } catch (\Throwable $e) {
                    Log::warning("Failed to send password reset email to {$user->email}: " . $e->getMessage());
                }
            }

            // In local/testing development environment or via HR Admin notification
            session()->flash('dev_reset_url', $resetUrl);
        }

        return back()->with('status', 'If your account exists and is eligible for password reset, a secure request has been processed.');
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

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        if (
            !$record ||
            !Hash::check($validated['token'], $record->token) ||
            Carbon::parse($record->created_at)->addMinutes($expireMinutes)->isPast()
        ) {
            return back()->withErrors(['email' => 'This password reset token is invalid or has expired.']);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Unable to find user with this email address.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        $this->auditLogger->log(
            action: 'auth.password_reset_completed',
            targetType: 'App\Models\User',
            targetId: $user->id,
            description: "User {$user->username} completed password reset with token."
        );

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. You may now sign in.');
    }
}
