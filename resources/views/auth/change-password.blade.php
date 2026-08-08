@extends('layouts.app')

@section('title', 'Change Password')
@section('page-title', 'Security Settings')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-slate-200">
        <div class="mb-6">
            <h3 class="text-lg font-bold text-slate-900">Change Password</h3>
            <p class="text-xs text-slate-500 mt-1">
                Enter your current password to set a new password for your account.
            </p>
        </div>

        <form method="POST" action="{{ route('password.change.post') }}" class="space-y-5">
            @csrf

            <div>
                <label for="current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                    Current Password
                </label>
                <div class="mt-1.5">
                    <input id="current_password" name="current_password" type="password" required
                        class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                    New Password
                </label>
                <div class="mt-1.5">
                    <input id="password" name="password" type="password" required minlength="8"
                        class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <p class="mt-1 text-[11px] text-slate-500">Must be at least 8 characters long.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                    Confirm New Password
                </label>
                <div class="mt-1.5">
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                        class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
