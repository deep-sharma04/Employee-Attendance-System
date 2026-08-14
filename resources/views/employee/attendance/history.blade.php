@extends('layouts.app')

@section('title', 'My Attendance History')
@section('page-title', 'My Attendance Records')

@section('header-actions')
    <form method="GET" action="{{ route('employee.attendance.history') }}" class="flex items-center gap-2">
        <select name="year" onchange="this.form.submit()"
            class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold">
            @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>Year {{ $y }}</option>
            @endfor
        </select>

        <select name="month" onchange="this.form.submit()"
            class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>
                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                </option>
            @endfor
        </select>
    </form>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Monthly Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Present Days</span>
            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                {{ $summary['present_days'] }}
            </p>
            <span class="text-[10px] text-slate-400">On-Time Punches</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Late Arrivals</span>
            <p class="text-xl font-black text-amber-500 mt-1">
                {{ $summary['late_days'] }}
            </p>
            <span class="text-[10px] text-slate-400">3 Late = 1 Absent penalty</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Half Days</span>
            <p class="text-xl font-black text-indigo-500 mt-1">
                {{ $summary['half_days'] }}
            </p>
            <span class="text-[10px] text-slate-400">2 Half Days = 1 Absent</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Direct Absents</span>
            <p class="text-xl font-black text-rose-500 mt-1">
                {{ $summary['direct_absent_days'] }}
            </p>
            <span class="text-[10px] text-slate-400">Unpunched Work Days</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Hours Logged</span>
            <p class="text-xl font-black text-slate-900 dark:text-white mt-1">
                {{ $summary['total_hours_worked'] }}h
            </p>
            <span class="text-[10px] text-slate-400">Shift Punch Hours</span>
        </div>

        <div class="bg-indigo-600 rounded-2xl p-4 text-white shadow-md shadow-indigo-600/20">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-indigo-200">Payable Days</span>
            <p class="text-xl font-black text-white mt-1">
                {{ $summary['total_payable_days'] }}
            </p>
            <span class="text-[10px] text-indigo-200">Net after conversion</span>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Daily Punch Logs — {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}</h3>
            <span class="text-xs text-slate-400">{{ $records->count() }} records logged</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Punch In</th>
                        <th class="py-3 px-4">Punch Out</th>
                        <th class="py-3 px-4">Total Hours</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Network IP</th>
                        <th class="py-3 px-4 text-right">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($records as $rec)
                        @php
                            $statusVal = $rec->status instanceof \App\Enums\AttendanceStatus ? $rec->status->value : (string) $rec->status;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $rec->attendance_date->format('M d, Y') }}</p>
                                <p class="text-[10px] text-slate-400">{{ $rec->attendance_date->format('l') }}</p>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-800 dark:text-slate-200">
                                {{ $rec->punch_in_at ? $rec->punch_in_at->format('H:i:s') : '—' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-800 dark:text-slate-200">
                                {{ $rec->punch_out_at ? $rec->punch_out_at->format('H:i:s') : '—' }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $rec->total_hours ? $rec->total_hours . ' hrs' : '—' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $statusVal === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : '' }}
                                    {{ $statusVal === 'late' ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400' : '' }}
                                    {{ $statusVal === 'half_day' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}
                                    {{ $statusVal === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400' : '' }}
                                    {{ $statusVal === 'holiday' ? 'bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400' : '' }}
                                    {{ $statusVal === 'week_off' ? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' : '' }}
                                    {{ $statusVal === 'leave' ? 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400' : '' }}">
                                    {{ str_replace('_', ' ', $statusVal) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $rec->punch_in_ip ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($rec->is_manually_corrected)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400" title="Reason: {{ $rec->correction_reason }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        HR Corrected
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400">Biometric Punch</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                No attendance records logged for this month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
