@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="space-y-5 relative z-10">

    <!-- Heading -->
    <div class="text-left">
        <div class="inline-flex items-center justify-center p-2.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 mb-3">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <h2 class="text-lg font-bold text-white">
            Reset Account Password
        </h2>
        <p class="mt-1 text-xs text-slate-400 leading-relaxed">
            Enter your <strong>Username</strong>, <strong>Email Address</strong>, or <strong>Employee Code</strong> (e.g. <code class="text-indigo-300 bg-slate-800/80 px-1 py-0.5 rounded">EMP-DEV-001</code>). We'll dispatch a secure recovery link to your inbox.
        </p>
    </div>

    <!-- Status Alerts -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-xs text-emerald-300 space-y-1">
            <div class="flex items-center gap-2 font-semibold">
                <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Request Processed</span>
            </div>
            <p class="text-emerald-200/90 leading-normal pl-6">
                {{ session('status') }}
            </p>
            @if(session('reset_sent_to'))
                <p class="text-[11px] text-emerald-400/80 pl-6">
                    Dispatched to: <span class="font-mono text-white font-medium">{{ session('reset_sent_to') }}</span>
                </p>
            @endif
        </div>
    @endif

    <!-- Local Dev Reset Link Helper -->
    @if(session('dev_reset_url'))
        <div class="rounded-xl border border-indigo-500/30 bg-slate-900/90 p-3.5 text-xs text-slate-300 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    Local Dev Helper Link
                </span>
                <span class="text-[10px] text-slate-500">Auto-Generated</span>
            </div>
            <p class="text-[11px] text-slate-400">
                You can immediately test the password reset flow using this generated link:
            </p>
            <div class="pt-1">
                <a href="{{ session('dev_reset_url') }}"
                   class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow-xs">
                    <span>Open Reset Password Form</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    @endif

    <!-- Forgot Password Form -->
    <form method="POST" action="{{ route('password.forgot.post') }}" class="space-y-4" id="forgot-form">
        @csrf

        <!-- Username / Email / Employee ID Field -->
        <div>
            <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Username / Email / Employee Code
            </label>

            <div class="mt-1.5 relative">
                <input
                    id="username"
                    name="username"
                    type="text"
                    required
                    autofocus
                    value="{{ old('username') }}"
                    placeholder="e.g. employee, EMP-DEV-001, or name@hrm.local"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/90 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-all"
                >
            </div>

            @error('username')
                <p class="mt-1.5 text-xs text-rose-400 font-medium flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Demo Quick Select Shortcuts -->
        <div class="pt-1">
            <div class="text-[11px] font-medium text-slate-400 mb-1.5 flex items-center justify-between">
                <span>Quick Fill Demo Accounts:</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" onclick="document.getElementById('username').value='employee'" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/60 transition-colors">
                    Employee
                </button>
                <button type="button" onclick="document.getElementById('username').value='EMP-DEV-001'" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/60 transition-colors">
                    EMP-DEV-001
                </button>
                <button type="button" onclick="document.getElementById('username').value='manager'" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/60 transition-colors">
                    Manager
                </button>
                <button type="button" onclick="document.getElementById('username').value='admin'" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/60 transition-colors">
                    Admin
                </button>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button
                type="submit"
                id="submit-reset-btn"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-600/30 transition-all cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Send Reset Link via SMTP</span>
            </button>
        </div>

        <!-- Back to Login -->
        <div class="text-center pt-2 border-t border-slate-800/80">
            <a href="{{ route('login') }}" class="text-xs font-medium text-slate-400 hover:text-white transition-colors inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Sign In</span>
            </a>
        </div>
    </form>
</div>
@endsection
