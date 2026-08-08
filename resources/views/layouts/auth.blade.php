<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') — HRM Enterprise Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Logo Badge -->
        <div class="mx-auto h-12 w-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-indigo-400 flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-indigo-500/30 border border-indigo-300/30">
            H
        </div>
        <h1 class="mt-4 text-2xl font-black tracking-tight text-white sm:text-3xl">
            HRM Enterprise Portal
        </h1>
        <p class="mt-1.5 text-xs text-slate-400 font-medium">
            Attendance, Leave & Payroll Management System
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="glass-panel-dark rounded-2xl p-6 sm:p-8 shadow-2xl border border-slate-800">
            <!-- Flash Errors -->
            @include('layouts.partials.flash')

            @yield('content')
        </div>

        <!-- Security Badge -->
        <div class="mt-6 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
            <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Protected by Role-Based Access Control & IP Allowlist</span>
        </div>
    </div>
</body>
</html>
