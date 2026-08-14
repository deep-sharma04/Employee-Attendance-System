
@extends('layouts.app')

@section('title', 'HR Admin Dashboard')
@section('page-title', 'HR Operations Workspace')

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Employees</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['active_employees'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-slate-500">Total: {{ $stats['total_employees'] ?? 0 }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Leaves</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['pending_leaves'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-amber-600">Requests to approve</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Documents</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['pending_documents'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-purple-600">Verification needed</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </div>
        </div>

        <!-- Current Payroll Cycle Status Widget (T127) -->
        <a href="{{ route('hr-admin.payroll.index') }}" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between hover:border-indigo-400 transition-colors">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Payroll Status</p>
                <h3 class="text-sm font-bold text-indigo-700 mt-1">{{ $payrollStatus ?? 'Not Generated' }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ $stats['current_payroll_count'] ?? 0 }} generated</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </a>
    </div>

    <!-- Today's Attendance Breakdown (T138) -->
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

    <!-- Quick Operations & Reports Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('hr-admin.employees.create') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Add New Employee</h4>
            <p class="text-xs text-slate-500 mt-1">Capture personal, salary, bank details and assign shift/leaves.</p>
        </a>

        <a href="{{ route('hr-admin.attendance.index') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Attendance Monitoring & Correction</h4>
            <p class="text-xs text-slate-500 mt-1">Monitor daily roster and correct punches with mandatory reason logging.</p>
        </a>

        <a href="{{ route('hr-admin.payroll.index') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Generate Monthly Payroll</h4>
            <p class="text-xs text-slate-500 mt-1">Calculate LOP deductions, net pay, and prepare for Super Admin approval.</p>
        </a>
    </div>

    <!-- Quick Reports Access -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <h3 class="text-sm font-bold text-slate-900 mb-4">Quick Reports Access</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('hr-admin.reports.attendance') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <span>Attendance Reports</span>
            </a>
            <a href="{{ route('hr-admin.reports.leave') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                <div class="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <span>Leave Reports</span>
            </a>
            <a href="{{ route('hr-admin.reports.payroll') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-100 hover:border-indigo-200 text-xs font-semibold text-slate-700 hover:text-indigo-700 transition-all">
                <div class="h-8 w-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span>Payroll Reports</span>
            </a>
        </div>
    </div>
</div>
@endsection
