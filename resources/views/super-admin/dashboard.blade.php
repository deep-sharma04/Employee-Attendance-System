
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
                <span class="text-[11px] font-medium text-emerald-600">Active: {{ $stats['active_employees'] ?? 0 }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Leaves</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['pending_leaves'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-amber-600">Awaiting review</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">HR Admins</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['hr_admins'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-purple-600">Active Admins</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>

        <!-- Payroll Cycle Status Widget (T127) -->
        <a href="{{ route('hr-admin.payroll.index') }}" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between hover:border-indigo-400 transition-colors">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Payroll Status</p>
                <h3 class="text-sm font-bold text-indigo-700 mt-1">{{ $payrollStatus ?? 'Not Generated' }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ $stats['current_payroll_count'] ?? 0 }} records</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </a>
    </div>

    <!-- Today's Attendance Breakdown (T137) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <h3 class="text-sm font-bold text-slate-900 mb-4">Today's Attendance Breakdown</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="flex items-center gap-3 bg-emerald-50/50 p-3 rounded-xl border border-emerald-100">
                <div class="h-9 w-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs">P</div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium uppercase">Present</p>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['present_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-amber-50/50 p-3 rounded-xl border border-amber-100">
                <div class="h-9 w-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-xs">L</div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium uppercase">Late</p>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['late_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-orange-50/50 p-3 rounded-xl border border-orange-100">
                <div class="h-9 w-9 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-xs">H</div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium uppercase">Half Day</p>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['half_day_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-rose-50/50 p-3 rounded-xl border border-rose-100">
                <div class="h-9 w-9 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-xs">A</div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium uppercase">Absent</p>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['absent_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                <div class="h-9 w-9 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">L</div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium uppercase">On Leave</p>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['leave_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operations & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Administrative Shortcuts -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs lg:col-span-1">
            <h3 class="text-sm font-bold text-slate-900 mb-4">Super Admin Operations</h3>
            <div class="space-y-3">
                <a href="{{ route('hr-admin.employees.create') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                    <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <span>Onboard New Employee</span>
                </a>

                <a href="{{ route('hr-admin.shifts.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                    <div class="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span>Manage Shifts & Grace Periods</span>
                </a>

                <a href="{{ route('hr-admin.ip-allowlists.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                    <div class="h-8 w-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <span>IP Allowlist Security</span>
                </a>

                <a href="{{ route('hr-admin.payroll.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                    <div class="h-8 w-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span>Review & Finalize Payroll</span>
                </a>
            </div>
        </div>

        <!-- System Audit Log Trail -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Recent System Audit Trail</h3>
                <a href="{{ route('super-admin.audit-logs.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View All &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-2.5 rounded-l-xl">Action</th>
                            <th class="px-4 py-2.5">Target</th>
                            <th class="px-4 py-2.5">Description</th>
                            <th class="px-4 py-2.5 rounded-r-xl text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentActivity as $log)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ $log->action }}</td>
                                <td class="px-4 py-3">{{ $log->target_type }} #{{ $log->target_id }}</td>
                                <td class="px-4 py-3 text-slate-500 truncate max-w-xs">{{ $log->description ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono text-[11px]">
                                    {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">No activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
