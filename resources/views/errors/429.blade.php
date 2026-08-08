<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 — Too Many Requests</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-center text-slate-100">
    <div class="glass-panel-dark max-w-lg w-full rounded-2xl p-8 shadow-2xl border border-slate-800">
        <div class="mx-auto h-14 w-14 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-center text-amber-400 text-2xl font-bold">
            &timer;
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">429 Rate Limit Exceeded</h1>
        <p class="mt-2 text-sm text-slate-300">
            Too many authentication or write attempts. For security reasons, please pause for 60 seconds before retrying.
        </p>
        <div class="mt-6 flex justify-center">
            <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md transition-all">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
