<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'HRM System') — Attendance, Leave & Payroll</title>

    <!-- Google Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-50 selection:bg-indigo-500 selection:text-white">
    <div class="min-h-screen flex">
        <!-- Sidebar Navigation -->
        @include('layouts.partials.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
            <!-- Top Header Navbar -->
            @include('layouts.partials.header')

            <!-- Page Body Content -->
            <main class="flex-1 p-6 md:p-8 max-w-7xl w-full mx-auto">
                <!-- Flash Messages (Success / Error / Notice) -->
                @include('layouts.partials.flash')

                <!-- View Specific Content -->
                @yield('content')
            </main>

            <!-- Bottom System Footer -->
            @include('layouts.partials.footer')
        </div>
    </div>

    <!-- Live Time Tick Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clockEl = document.getElementById('header-live-clock');
            if (clockEl) {
                setInterval(() => {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    clockEl.textContent = `${hours}:${minutes}:${seconds}`;
                }, 1000);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
