@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="space-y-4">
    <div class="text-left mb-4">
        <h3 class="text-base font-bold text-white">Reset Account Password</h3>
        <p class="text-xs text-slate-400 mt-1">
            Enter your username. For employee accounts, HR Administration can securely verify and regenerate your access credentials.
        </p>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-indigo-400/30 bg-indigo-500/10 p-3.5 text-xs text-indigo-300">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.forgot.post') }}" class="space-y-4">
        @csrf

        <div>
            <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Username / Employee ID
            </label>
            <div class="mt-1.5">
                <input id="username" name="username" type="text" required
                    placeholder="e.g. EMP001 or john.doe"
                    class="block w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
                Submit Password Request
            </button>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-medium text-slate-400 hover:text-white transition-colors">
                &larr; Back to Login
            </a>
        </div>
    </form>
</div>
@endsection
