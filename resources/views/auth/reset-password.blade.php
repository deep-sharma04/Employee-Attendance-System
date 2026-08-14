@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<div class="space-y-4">
    <div class="text-left mb-4">
        <h3 class="text-base font-bold text-white">Choose a New Password</h3>
        <p class="text-xs text-slate-400 mt-1">
            Please enter your email address and choose a secure password (at least 8 characters).
        </p>
    </div>

    <form method="POST" action="{{ route('password.reset.post') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Email Address
            </label>
            <div class="mt-1.5">
                <input id="email" name="email" type="email" required
                    value="{{ old('email', $email ?? '') }}"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            @error('email')
                <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                New Password
            </label>
            <div class="mt-1.5">
                <input id="password" name="password" type="password" required minlength="8"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            @error('password')
                <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Confirm New Password
            </label>
            <div class="mt-1.5">
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
                Reset & Update Password
            </button>
        </div>
    </form>
</div>
@endsection
