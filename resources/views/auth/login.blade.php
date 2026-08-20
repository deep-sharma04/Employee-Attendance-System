@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="space-y-5 relative z-10">

    <!-- Heading -->
    <div>
        <h2 class="text-lg font-bold text-white">
            Sign in to your account
        </h2>

        <p class="mt-0.5 text-xs text-slate-400">
            Use the credentials provided by your Admin or HR.
        </p>
    </div>

    <!-- Login Form -->
    <form
        method="POST"
        action="{{ route('login.post') }}"
        class="space-y-4"
        id="login-form"
    >
        @csrf

        <!-- Role Selector -->
        <div>
            <label
                class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2"
            >
                Select Your Role 
            </label>

            <!-- Compact Pills -->
            <div
                class="flex flex-wrap gap-1.5"
                id="role-options"
            >

                <!-- Super Admin -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="super_admin"
                        class="role-input"
                        {{ old('role') === 'super_admin' ? 'checked' : '' }}
                    >

                    <div class="role-card role-admin">
                        <span class="role-dot"></span>
                        <span>Admin</span>
                    </div>
                </label>

                <!-- HR Admin -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="hr_admin"
                        class="role-input"
                        {{ old('role') === 'hr_admin' ? 'checked' : '' }}
                    >

                    <div class="role-card role-hr">
                        <span class="role-dot"></span>
                        <span>HR Admin</span>
                    </div>
                </label>

                <!-- Manager -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="manager"
                        class="role-input"
                        {{ old('role') === 'manager' ? 'checked' : '' }}
                    >

                    <div class="role-card role-manager">
                        <span class="role-dot"></span>
                        <span>Manager</span>
                    </div>
                </label>

                <!-- Team Lead -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="team_lead"
                        class="role-input"
                        {{ old('role') === 'team_lead' ? 'checked' : '' }}
                    >

                    <div class="role-card role-team-lead">
                        <span class="role-dot"></span>
                        <span>Team Lead</span>
                    </div>
                </label>

                <!-- Employee -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="employee"
                        class="role-input"
                        {{ old('role') === 'employee' ? 'checked' : '' }}
                    >

                    <div class="role-card role-employee">
                        <span class="role-dot"></span>
                        <span>Employee</span>
                    </div>
                </label>

                <!-- Client -->
                <label class="role-option">
                    <input
                        type="radio"
                        name="role"
                        value="client"
                        class="role-input"
                        {{ old('role') === 'client' ? 'checked' : '' }}
                    >

                    <div class="role-card role-client">
                        <span class="role-dot"></span>
                        <span>Client</span>
                    </div>
                </label>

            </div>

            @error('role')
                <p class="mt-1 text-xs text-rose-400 font-medium">
                    {{ $message }}
                </p>
            @enderror
        </div>


        <!-- Username -->
        <div>
            <label
                for="username"
                class="block text-xs font-semibold uppercase tracking-wider text-slate-300"
            >
                Username
            </label>

            <div class="mt-1.5">
                <input
                    id="username"
                    name="username"
                    type="text"
                    autocomplete="username"
                    required
                    value="{{ old('username') }}"
                    placeholder="Enter your username"
                    class="block w-full rounded-xl border border-slate-700
                           bg-slate-800/90 px-4 py-2.5 text-sm text-white
                           placeholder-slate-500
                           focus:border-indigo-500
                           focus:outline-none
                           focus:ring-2 focus:ring-indigo-500/30
                           transition-all"
                >
            </div>

            @error('username')
                <p class="mt-1 text-xs text-rose-400 font-medium">
                    {{ $message }}
                </p>
            @enderror
        </div>


        <!-- Password -->
        <div>

            <div class="flex items-center justify-between">

                <label
                    for="password"
                    class="block text-xs font-semibold uppercase tracking-wider text-slate-300"
                >
                    Password
                </label>

                <a
                    href="{{ route('password.forgot') }}"
                    class="text-[11px] font-medium text-indigo-400
                           hover:text-indigo-300 transition-colors"
                >
                    Forgot password?
                </a>

            </div>

            <div class="mt-1.5">
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    placeholder="Enter your password"
                    class="block w-full rounded-xl border border-slate-700
                           bg-slate-800/90 px-4 py-2.5 text-sm text-white
                           placeholder-slate-500
                           focus:border-indigo-500
                           focus:outline-none
                           focus:ring-2 focus:ring-indigo-500/30
                           transition-all"
                >
            </div>

            @error('password')
                <p class="mt-1 text-xs text-rose-400 font-medium">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <!-- Remember Me -->
        <div class="flex items-center pt-0.5">

            <input
                id="remember"
                name="remember"
                type="checkbox"
                value="1"
                {{ old('remember') ? 'checked' : '' }}
                class="h-3.5 w-3.5 rounded border-slate-700
                       bg-slate-800 text-indigo-600
                       focus:ring-indigo-500 cursor-pointer"
            >

            <label
                for="remember"
                class="ml-2 block text-xs text-slate-400 cursor-pointer"
            >
                Remember me
            </label>

        </div>


        <!-- Submit -->
        <button
            type="submit"
            class="w-full flex justify-center items-center
                   py-2.5 px-4 rounded-xl text-sm font-bold text-white
                   bg-indigo-600 hover:bg-indigo-500
                   focus:outline-none
                   focus:ring-2 focus:ring-offset-2
                   focus:ring-offset-slate-900
                   focus:ring-indigo-500
                   shadow-lg shadow-indigo-600/25
                   transition-all cursor-pointer"
        >
            Sign In
        </button>

    </form>

</div>


<!-- Role Card Styling -->
<style>

    /*
     * Role selector
     * Compact inline pills: [Admin][HR Admin][Manager]...
     */

    .role-option {
        display: inline-flex;
        cursor: pointer;
    }

    /*
     * Completely hide the native radio button.
     */
    .role-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /*
     * Role pill
     */
    .role-card {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        padding: 4px 10px;

        border: 1px solid #334155;
        border-radius: 9999px;

        background: rgba(30, 41, 59, 0.55);

        color: #cbd5e1;

        font-size: 11px;
        font-weight: 600;
        line-height: 1.4;

        white-space: nowrap;

        transition:
            border-color 0.15s ease,
            background-color 0.15s ease,
            color 0.15s ease,
            box-shadow 0.15s ease,
            transform 0.15s ease;

        box-sizing: border-box;
    }

    /*
     * Hover
     */
    .role-option:hover .role-card {
        border-color: #475569;
        transform: translateY(-1px);
    }

    /*
     * Dot
     */
    .role-dot {
        width: 6px;
        height: 6px;
        min-width: 6px;

        border-radius: 9999px;

        display: inline-block;
    }

    /*
     * Role colors
     */
    .role-admin .role-dot {
        background: #fbbf24;
    }

    .role-hr .role-dot {
        background: #60a5fa;
    }

    .role-manager .role-dot {
        background: #c084fc;
    }

    .role-team-lead .role-dot {
        background: #38bdf8;
    }

    .role-employee .role-dot {
        background: #818cf8;
    }

    .role-client .role-dot {
        background: #34d399;
    }

    /*
     * Selected states
     */

    .role-input:checked + .role-card {
        color: #ffffff;
        background: rgba(99, 102, 241, 0.15);
        border-color: #6366f1;

        box-shadow:
            0 0 0 1px rgba(99, 102, 241, 0.2),
            0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /*
     * Individual selected border colors
     */

    .role-input[value="super_admin"]:checked + .role-card {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.12);
    }

    .role-input[value="hr_admin"]:checked + .role-card {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.12);
    }

    .role-input[value="manager"]:checked + .role-card {
        border-color: #a855f7;
        background: rgba(168, 85, 247, 0.12);
    }

    .role-input[value="team_lead"]:checked + .role-card {
        border-color: #0ea5e9;
        background: rgba(14, 165, 233, 0.12);
    }

    .role-input[value="employee"]:checked + .role-card {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.12);
    }

    .role-input[value="client"]:checked + .role-card {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.12);
    }

    /*
     * Mobile:
     * Pills naturally wrap, no special handling needed.
     */
    @media (max-width: 420px) {
        .role-card {
            font-size: 10px;
            padding: 3px 8px;
        }
    }

</style>

@endsection