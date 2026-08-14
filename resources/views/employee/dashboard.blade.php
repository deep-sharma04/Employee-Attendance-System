@extends('layouts.app')

@section('title', 'Employee Self-Service')
@section('page-title', 'My Attendance & Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Punch Widget Card -->
    <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-indigo-800/40">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                        Approved Office Network Active
                    </span>

                    @if($todayAttendance)
                        @php
                            // Fix: Safely cast Enum to string
                            $rawStatus = $todayAttendance->status;
                            $todayStatus = $rawStatus instanceof \App\Enums\AttendanceStatus ? $rawStatus->value : ($rawStatus ?? 'present');
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                            {{ $todayStatus === 'present' ? 'bg-emerald-400/20 text-emerald-300' : '' }}
                            {{ $todayStatus === 'late' ? 'bg-amber-400/20 text-amber-300' : '' }}
                            {{ $todayStatus === 'half_day' ? 'bg-indigo-400/20 text-indigo-300' : '' }}">
                            Today: {{ str_replace('_', ' ', $todayStatus) }}
                        </span>
                    @endif
                </div>

                <h3 class="text-2xl font-bold mt-2">Daily Attendance Punch</h3>
                <p class="text-xs text-slate-300 mt-1 max-w-md">
                    @if(!$todayAttendance || !$todayAttendance->punch_in)
                        Record your shift punch in from the office network. Late arrivals beyond 15 mins count as Late.
                    @elseif(!$todayAttendance->punch_out)
                        You punched in at <strong class="text-white">{{ substr($todayAttendance->punch_in, 11, 5) }}</strong>. Remember to punch out before leaving.
                    @else
                        Shift completed! Punched in at <strong class="text-white">{{ substr($todayAttendance->punch_in_at, 11, 5) }}</strong> and out at <strong class="text-white">{{ substr($todayAttendance->punch_out_at, 11, 5) }}</strong> (Total: {{ $todayAttendance->total_hours }} hrs).
                    @endif
                </p>
            </div>

            <!-- Punch Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                @if(!$todayAttendance || !$todayAttendance->punch_in)
                    <form method="POST" action="{{ route('employee.attendance.punch-in') }}">
                        @csrf
                        <button type="submit"
                            class="px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 transition-all flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            Punch In Now
                        </button>
                    </form>
                @elseif(!$todayAttendance->punch_out)
                    <form method="POST" action="{{ route('employee.attendance.punch-out') }}">
                        @csrf
                        <button type="submit"
                            class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-white font-bold text-sm shadow-lg shadow-amber-500/30 transition-all flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Punch Out (End Shift)
                        </button>
                    </form>
                @else
                    <div class="px-5 py-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Day Completed ({{ $todayAttendance->total_hours }}h)
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Stats & Links Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Leave Balances & Pending Requests (T139) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Leave Balances</h4>
                <a href="{{ route('employee.leaves.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">
                    Pending: {{ $pendingLeaves ?? 0 }}
                </a>
            </div>
            <div class="space-y-3">
                @forelse($leaveBalances as $balance)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/40">
                        <span class="text-xs font-semibold text-indigo-900 dark:text-indigo-300">{{ $balance->leaveType->name ?? $balance->name }}</span>
                        <span class="text-base font-bold text-indigo-700 dark:text-indigo-400">{{ $balance->remaining_days }} Days</span>
                    </div>
                @empty
                    <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50/50 border border-blue-100">
                        <span class="text-xs font-semibold text-blue-900">Casual Leave</span>
                        <span class="text-base font-bold text-blue-700">0 Days</span>
                    </div>
                @endforelse
            </div>
            <a href="{{ route('employee.leaves.create') }}" class="mt-4 block text-center py-2 px-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 text-xs font-semibold transition-colors">
                Apply for Leave &rarr;
            </a>
        </div>

        <!-- Profile & Attendance Logs (T139) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">My Profile & Shift</h4>
            <div class="mt-4 text-xs space-y-2 text-slate-600 dark:text-slate-300">
                <p><strong class="text-slate-800 dark:text-white">Code:</strong> {{ $employee->employee_code ?? 'EMP---' }}</p>
                <p><strong class="text-slate-800 dark:text-white">Designation:</strong> {{ $employee->designation ?? 'Team Member' }}</p>
                <p><strong class="text-slate-800 dark:text-white">Department:</strong> {{ $employee->department ?? 'General' }}</p>
                <p><strong class="text-slate-800 dark:text-white">Shift:</strong> Standard Day (09:00 - 18:00)</p>
            </div>
            <div class="mt-6 space-y-2">
                <a href="{{ route('employee.attendance.history') }}" class="block text-center py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-colors">
                    My Attendance History &rarr;
                </a>
                <a href="{{ route('employee.profile') }}" class="block text-center py-2 px-4 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-800 dark:text-slate-200 text-xs font-semibold transition-colors">
                    View Full Profile &rarr;
                </a>
            </div>
        </div>

        <!-- Recent Payslips (T139) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recent Payslips</h4>
            <div class="mt-4 space-y-2">
                @forelse($recentPayslips as $payslip)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-700/40">
                        <div class="text-xs text-slate-700 dark:text-slate-200">
                            {{ \Carbon\Carbon::create()->month($payslip->payroll_month)->format('F Y') }}
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-emerald-600">₹{{ number_format($payslip->net_salary, 2) }}</span>
                            @if($payslip->payslip)
                                <a href="{{ route('employee.payslips.download', $payslip->payslip->id) }}" class="text-indigo-600 hover:underline text-xs font-bold">Download</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 text-center py-4">No payslips available yet.</p>
                @endforelse
            </div>
            <a href="{{ route('employee.payslips.index') }}" class="mt-4 block text-center py-2 px-4 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-800 dark:text-slate-200 text-xs font-semibold transition-colors">
                View All Payslips &rarr;
            </a>
        </div>
    </div>

    <!-- Notifications Section (T149) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">System Notifications</h4>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400 text-center py-4 border-2 border-dashed border-slate-100 dark:border-slate-700 rounded-xl">
            No new notifications at this time.
        </div>
    </div>
</div>
@endsection