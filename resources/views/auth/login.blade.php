@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<form method="POST" action="{{ route('login.post') }}" class="space-y-5">
    @csrf

    <div>
        <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
            Username
        </label>
        <div class="mt-1.5">
            <input id="username" name="username" type="text" autocomplete="username" required
                value="{{ old('username') }}"
                placeholder="Enter your assigned username"
                class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
        </div>
        @error('username')
            <p class="mt-1.5 text-xs text-rose-400 font-medium">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Password
            </label>
            <a href="{{ route('password.forgot') }}" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                Forgot password?
            </a>
        </div>
        <div class="mt-1.5">
            <input id="password" name="password" type="password" autocomplete="current-password" required
                placeholder="••••••••"
                class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
        </div>
        @error('password')
            <p class="mt-1.5 text-xs text-rose-400 font-medium">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center">
        <input id="remember" name="remember" type="checkbox"
            class="h-4 w-4 rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-indigo-500">
        <label for="remember" class="ml-2.5 block text-xs text-slate-300">
            Remember this session on approved device
        </label>
    </div>

    <div>
        <button type="submit"
            class="w-full flex justify-center py-2.5 px-4 rounded-xl border border-transparent text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
            Sign In to Portal
        </button>
    </div>
</form>
@endsection
