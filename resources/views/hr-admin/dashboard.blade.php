@extends('layouts.app')

@section('title', 'HR Admin Dashboard')
@section('page-title', 'HR Operations Workspace')

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
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
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Attendance</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['today_attendance_count'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-amber-600">Late: {{ $stats['today_late_count'] ?? 0 }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
    </div>

    <!-- Quick Operations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('hr-admin.employees.create') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Add New Employee</h4>
            <p class="text-xs text-slate-500 mt-1">Capture personal, salary, bank details and assign shift/leaves.</p>
        </a>

        <a href="{{ route('hr-admin.attendance.correct') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Manual Attendance Correction</h4>
            <p class="text-xs text-slate-500 mt-1">Correct or record past punches with mandatory reason logging.</p>
        </a>

        <a href="{{ route('hr-admin.payroll.index') }}" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:border-indigo-500 transition-all">
            <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">Generate Monthly Payroll</h4>
            <p class="text-xs text-slate-500 mt-1">Calculate LOP deductions, net pay, and prepare for Super Admin approval.</p>
        </a>
    </div>
</div>
@endsection
