@extends('layouts.app')

@section('title', 'Employee Self-Service')
@section('page-title', 'My Attendance & Leave')

@section('content')
<div class="space-y-6">
    <!-- Punch Widget Card -->
    <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-indigo-800/40">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Approved Office Network Active
                </span>
                <h3 class="text-2xl font-bold mt-2">Daily Attendance Punch</h3>
                <p class="text-xs text-slate-300 mt-1 max-w-md">
                    Capture your shift punch in and punch out from the office network. Late arrivals beyond 15 mins are recorded.
                </p>
            </div>

            <!-- Punch Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('employee.attendance.punch-in') }}">
                    @csrf
                    <input type="hidden" name="action" value="punch_in">
                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 transition-all flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                        Punch In
                    </button>
                </form>

                <form method="POST" action="{{ route('employee.attendance.punch-out') }}">
                    @csrf
                    <input type="hidden" name="action" value="punch_out">
                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm border border-slate-700 shadow-md transition-all flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        Punch Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave Balances & Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Leave Balance</h4>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50/50 border border-blue-100">
                    <span class="text-xs font-semibold text-blue-900">Casual Leave</span>
                    <span class="text-base font-bold text-blue-700">Available</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-purple-50/50 border border-purple-100">
                    <span class="text-xs font-semibold text-purple-900">Medical Leave</span>
                    <span class="text-base font-bold text-purple-700">Available</span>
                </div>
            </div>
            <a href="{{ route('employee.leaves.create') }}" class="mt-4 block text-center py-2 px-4 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs font-semibold transition-colors">
                Apply for Leave &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">My Profile & Shift</h4>
            <div class="mt-4 text-xs space-y-2 text-slate-600">
                <p><strong class="text-slate-800">Code:</strong> {{ $employee->employee_code ?? 'EMP---' }}</p>
                <p><strong class="text-slate-800">Designation:</strong> {{ $employee->designation ?? 'Team Member' }}</p>
                <p><strong class="text-slate-800">Department:</strong> {{ $employee->department ?? 'General' }}</p>
                <p><strong class="text-slate-800">Shift:</strong> Standard Day (09:00 - 18:00)</p>
            </div>
            <a href="{{ route('employee.profile') }}" class="mt-4 block text-center py-2 px-4 rounded-xl bg-slate-50 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition-colors">
                View Full Profile &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Payslips</h4>
            <p class="text-xs text-slate-500 mt-2">
                Access your monthly finalized salary slips with LOP calculations and deductions.
            </p>
            <a href="{{ route('employee.payslips.index') }}" class="mt-6 block text-center py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors">
                View My Payslips &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
