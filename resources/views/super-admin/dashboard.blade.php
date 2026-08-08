@extends('layouts.app')

@section('title', 'Super Admin Dashboard')
@section('page-title', 'Super Admin Overview')

@section('content')
<div class="space-y-6">
    <!-- Top Stat Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Employees</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_employees'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Active workforce: {{ $stats['active_employees'] ?? 0 }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Attendance</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['today_attendance_count'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-indigo-600">Live Office Punches</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Leaves</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['pending_leaves'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-amber-600">Awaiting HR review</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">HR Admins</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['hr_admins'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-purple-600">Operational Accounts</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>
    </div>

    <!-- Quick Operations & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Administrative Shortcuts -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs lg:col-span-1">
            <h3 class="text-sm font-bold text-slate-900 mb-4">Super Admin Operations</h3>
            <div class="space-y-3">
                <a href="{{ route('super-admin.hr-admins.create') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200 transition-colors">
                    <span class="text-xs font-semibold text-slate-700">Add New HR Admin</span>
                    <span class="text-indigo-600 font-bold">&rarr;</span>
                </a>
                <a href="{{ route('super-admin.settings.index') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200 transition-colors">
                    <span class="text-xs font-semibold text-slate-700">Company & Payslip Settings</span>
                    <span class="text-indigo-600 font-bold">&rarr;</span>
                </a>
                <a href="{{ route('super-admin.audit-logs.index') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200 transition-colors">
                    <span class="text-xs font-semibold text-slate-700">Audit Logs & History</span>
                    <span class="text-indigo-600 font-bold">&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Recent Audit Trail Feed -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Recent Administrative Audit Logs</h3>
                <a href="{{ route('super-admin.audit-logs.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                    View Complete Log &rarr;
                </a>
            </div>

            @if(count($recentActivity) > 0)
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($recentActivity as $log)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-slate-800">{{ $log->actor_name }}</span>
                                <span class="text-slate-500">({{ $log->action }}) on {{ $log->target_type }}</span>
                            </div>
                            <span class="text-slate-400 font-mono text-[11px]">{{ $log->created_at }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-slate-400 text-xs">
                    No recent administrative logs recorded yet. System initial baseline active.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
