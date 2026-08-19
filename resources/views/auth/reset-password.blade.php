@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<div class="space-y-5 relative z-10">
    <div class="text-left">
        <div class="inline-flex items-center justify-center p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-3">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="text-lg font-bold text-white">Choose a New Password</h2>
        <p class="text-xs text-slate-400 mt-1">
            Choose a strong password with at least 8 characters to secure your account.
        </p>
    </div>

    <form method="POST" action="{{ route('password.reset.post') }}" class="space-y-4" id="reset-password-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Registered Email Address
            </label>
            <div class="mt-1.5">
                <input id="email" name="email" type="email" required
                    value="{{ old('email', $email ?? '') }}"
                    placeholder="name@company.com"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/90 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-rose-400 font-medium flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- New Password -->
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                    New Password
                </label>
                <span class="text-[10px] text-slate-400">Min. 8 characters</span>
            </div>
            <div class="mt-1.5 relative">
                <input id="password" name="password" type="password" required minlength="8"
                    placeholder="Enter strong password"
                    oninput="checkPasswordStrength(this.value)"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/90 px-4 py-2.5 pr-10 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all">
                <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <!-- Strength Bar -->
            <div class="mt-1.5 h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                <div id="strength-bar" class="h-full w-0 transition-all duration-300 bg-rose-500"></div>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-400 font-medium flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Confirm New Password
            </label>
            <div class="mt-1.5 relative">
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                    placeholder="Re-enter new password"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/90 px-4 py-2.5 pr-10 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all">
                <button type="button" onclick="togglePassword('password_confirmation')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-600/30 transition-all cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save New Password & Sign In</span>
            </button>
        </div>

        <div class="text-center pt-2 border-t border-slate-800/80">
            <a href="{{ route('login') }}" class="text-xs font-medium text-slate-400 hover:text-white transition-colors">
                &larr; Return to Sign In
            </a>
        </div>
    </form>
</div>

<script>
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    function checkPasswordStrength(val) {
        const bar = document.getElementById('strength-bar');
        if (!bar) return;
        let strength = 0;
        if (val.length >= 8) strength += 25;
        if (/[A-Z]/.test(val)) strength += 25;
        if (/[0-9]/.test(val)) strength += 25;
        if (/[^A-Za-z0-9]/.test(val)) strength += 25;

        bar.style.width = strength + '%';
        if (strength <= 25) {
            bar.className = 'h-full transition-all duration-300 bg-rose-500';
        } else if (strength <= 50) {
            bar.className = 'h-full transition-all duration-300 bg-amber-500';
        } else if (strength <= 75) {
            bar.className = 'h-full transition-all duration-300 bg-blue-500';
        } else {
            bar.className = 'h-full transition-all duration-300 bg-emerald-500';
        }
    }
</script>
@endsection
