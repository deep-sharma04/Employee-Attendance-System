<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — System Error</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-center text-slate-100">
    <div class="glass-panel-dark max-w-lg w-full rounded-2xl p-8 shadow-2xl border border-slate-800">
        <div class="mx-auto h-14 w-14 rounded-2xl bg-rose-500/20 border border-rose-400/30 flex items-center justify-center text-rose-400 text-2xl font-bold">
            &times;
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">500 Server Error</h1>
        <p class="mt-2 text-sm text-slate-300">
            An unexpected error occurred while processing your request. Internal technical details have been logged safely.
        </p>
        <div class="mt-6 flex justify-center">
            <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md transition-all">
                Return to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
