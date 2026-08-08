<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Office IP Restriction — Attendance Unauthorized</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-center text-slate-100">
    <div class="glass-panel-dark max-w-lg w-full rounded-2xl p-8 shadow-2xl border border-slate-800">
        <div class="mx-auto h-14 w-14 rounded-2xl bg-rose-500/20 border border-rose-400/30 flex items-center justify-center text-rose-400 text-2xl font-bold">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-black tracking-tight text-white">Office Network Required</h1>
        <p class="mt-2 text-xs text-slate-300">
            Attendance punch-in and punch-out are strictly restricted to authorized office networks.
        </p>
        <div class="mt-4 p-3 rounded-xl bg-slate-800/80 border border-slate-700 text-left font-mono text-xs text-slate-400">
            <div class="flex justify-between">
                <span>Detected IP:</span>
                <span class="text-rose-400 font-bold">{{ $clientIp ?? request()->ip() }}</span>
            </div>
            <div class="flex justify-between mt-1 text-[11px]">
                <span>Status:</span>
                <span class="text-amber-400">Unauthorized Network</span>
            </div>
        </div>
        <div class="mt-6 flex justify-center">
            <a href="{{ route('employee.dashboard') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md transition-all">
                Return to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
