@php
    $user = Auth::user();
    $role = $user?->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user?->role;
@endphp

<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 min-h-screen border-r border-slate-800 transition-all duration-300">
    <!-- Brand Header -->
    <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-800/80 bg-slate-950/60">
        <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white font-bold text-lg shadow-md shadow-indigo-500/20">
            H
        </div>
        <div>
            <h1 class="font-bold text-white tracking-tight text-base leading-tight">HRM System</h1>
            <span class="text-[10px] font-medium tracking-wider uppercase text-indigo-400">Enterprise Suite</span>
        </div>
    </div>

    <!-- User Mini Badge -->
    <div class="px-4 py-3 mx-3 my-3 rounded-xl bg-slate-800/50 border border-slate-700/50 flex items-center gap-3">
        <div class="h-8 w-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 font-semibold text-xs">
            {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
        </div>
        <div class="overflow-hidden">
            <p class="text-xs font-semibold text-white truncate">{{ $user?->name ?? 'Authenticated User' }}</p>
            <span class="inline-flex items-center text-[10px] font-medium text-slate-400">
                @if($role === 'super_admin')
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400 mr-1.5"></span> Super Admin
                @elseif($role === 'hr_admin')
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-400 mr-1.5"></span> HR Admin
                @elseif($role === 'manager')
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-400 mr-1.5"></span> Manager
                @elseif($role === 'team_lead')
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 mr-1.5"></span> Team Lead
                @elseif($role === 'client')
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 mr-1.5"></span> Client
                @else
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 mr-1.5"></span> Employee
                @endif
            </span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 space-y-1.5 overflow-y-auto sidebar-scroll text-sm">
        @if($role === 'super_admin')
            <!-- Super Admin Navigation -->
            <div class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administration</div>
            
            <a href="{{ route('super-admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('super-admin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                Dashboard
            </a>

            <a href="{{ route('super-admin.hr-admins.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('super-admin.hr-admins.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                HR Admin Accounts
            </a>

            <a href="{{ route('super-admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('super-admin.settings.index') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Company Settings
            </a>

            <a href="{{ route('super-admin.settings.project-health') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('super-admin.settings.project-health*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Project Health Engine
            </a>

            <a href="{{ route('super-admin.audit-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('super-admin.audit-logs.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Audit Trails
            </a>
        @endif

        @if($role === 'super_admin' || $role === 'hr_admin')
            <!-- HR Operations -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">HR Operations</div>

            <a href="{{ route('hr-admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                HR Workspace
            </a>

            <a href="{{ route('hr-admin.employees.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.employees.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Employees
            </a>

            <a href="{{ route('hr-admin.attendance.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.attendance.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Attendance
            </a>

            <a href="{{ route('hr-admin.leaves.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.leaves.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Leaves
            </a>

            <a href="{{ route('hr-admin.payroll.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.payroll.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Payroll Runs
            </a>

            <a href="{{ route('hr-admin.reports.attendance') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.reports.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Analytics & Reports
            </a>

            <a href="{{ route('hr-admin.shifts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.shifts.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
            </a>

            <a href="{{ route('hr-admin.holidays.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.holidays.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Holiday Calendar
            </a>

            <a href="{{ route('hr-admin.audit-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('hr-admin.audit-logs.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                Activity Log
            </a>
        @endif

        @if($role === 'manager' || $role === 'super_admin')
            <!-- Project Management Area -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Projects & Teams</div>

            <a href="{{ route('manager.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                Manager Workspace
            </a>

            <a href="{{ route('manager.projects.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.projects.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                Projects Directory
            </a>

            <a href="{{ route('manager.tasks.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.tasks.index') || request()->routeIs('manager.tasks.show') || request()->routeIs('manager.tasks.create') || request()->routeIs('manager.tasks.edit') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                Task Directory
            </a>

            <a href="{{ route('manager.tasks.kanban') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.tasks.kanban') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                Kanban Board
            </a>

            <a href="{{ route('manager.timesheets.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.timesheets.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Timesheet Approvals
            </a>

            <a href="{{ route('manager.clients.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.clients.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                Clients Directory
            </a>

            <a href="{{ route('manager.teams.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.teams.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Teams & Squads
            </a>

            <a href="{{ route('manager.employees.profiles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.employees.profiles.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                Resource Skills
            </a>

            <a href="{{ route('manager.knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.knowledge.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                Knowledge Base
            </a>

            <a href="{{ route('employee.attendance.history') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.attendance.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                My Attendance & Punch
            </a>

            <!-- Productivity & Reporting Links (Phase 28) -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reports & Insights</div>

            <a href="{{ route('manager.reports.executive') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.reports.executive') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Executive Dashboard
            </a>

            <a href="{{ route('manager.reports.productivity') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.reports.productivity') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Productivity Metrics
            </a>

            <a href="{{ route('manager.reports.workload') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.reports.workload') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Team Workload
            </a>

            <a href="{{ route('manager.reports.budget') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('manager.reports.budget') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Budget & Costs
            </a>
        @endif

        @if($role === 'team_lead')
            <!-- Team Lead Workspace -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Team Workspace</div>

            <a href="{{ route('team-lead.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                Dashboard
            </a>

            <a href="{{ route('team-lead.team.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.team.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                My Squad
            </a>

            <a href="{{ route('team-lead.tasks.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.tasks.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                Squad Tasks
            </a>

            <a href="{{ route('team-lead.timesheets.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.timesheets.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Timesheet Reviews
            </a>

            <a href="{{ route('team-lead.knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.knowledge.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                Knowledge Base
            </a>

            <a href="{{ route('employee.attendance.history') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.attendance.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                My Attendance & Punch
            </a>

            <!-- Productivity & Workload Links (Phase 28) -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reports</div>

            <a href="{{ route('team-lead.reports.productivity') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.reports.productivity') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Productivity Metrics
            </a>

            <a href="{{ route('team-lead.reports.workload') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('team-lead.reports.workload') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Squad Workload
            </a>
        @endif

        @if($role === 'client')
            <!-- Client Portal -->
            <div class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Portal</div>

            <a href="{{ route('client-portal.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('client-portal.dashboard') || request()->routeIs('client-portal.projects.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                Projects Overview
            </a>

            <a href="{{ route('client-portal.documents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('client-portal.documents.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Shared Documents
            </a>

            <a href="{{ route('client-portal.knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('client-portal.knowledge.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                Knowledge Search
            </a>
        @endif

        @if($role === 'employee')
            <!-- Employee Self-Service Navigation -->
            <div class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Self-Service</div>

            <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                My Dashboard
            </a>

            <a href="{{ route('employee.profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.profile') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                My Profile
            </a>

            <a href="{{ route('employee.tasks.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.tasks.index') || request()->routeIs('employee.tasks.show') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                My Tasks
            </a>

            <a href="{{ route('employee.tasks.recurring') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.tasks.recurring') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Recurring Tasks
            </a>

            <a href="{{ route('employee.timesheets.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.timesheets.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                My Timesheets
            </a>

            <a href="{{ route('employee.knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.knowledge.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                Knowledge Search
            </a>

            <a href="{{ route('employee.attendance.history') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.attendance.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Attendance History
            </a>

            <a href="{{ route('employee.leaves.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.leaves.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Leave Applications
            </a>

            <a href="{{ route('employee.payslips.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.payslips.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                My Payslips
            </a>

            <a href="{{ route('employee.holidays.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('employee.holidays.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Holiday Calendar
            </a>
        @endif
    </nav>

    <!-- Bottom Actions -->
    <div class="p-3 border-t border-slate-800 space-y-1">
        <a href="{{ route('password.change') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            Change Password
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors text-left">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>
