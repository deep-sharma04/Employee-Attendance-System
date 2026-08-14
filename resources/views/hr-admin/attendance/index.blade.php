@extends('layouts.app')

@section('title', 'Attendance Monitoring (' . $selectedDate . ')')
@section('page-title', 'Company Attendance Monitoring')

@section('header-actions')
    <form method="GET" action="{{ route('hr-admin.attendance.index') }}" class="flex flex-wrap items-center gap-2">
        <div>
            <label for="date" class="sr-only">Date</label>
            <input type="date" id="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()"
                class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold">
        </div>

        @if(count($departments) > 0)
            <div>
                <label for="department" class="sr-only">Department</label>
                <select id="department" name="department" onchange="this.form.submit()"
                    class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ $selectedDepartment === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </form>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Metrics Cards for Selected Date -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Active</span>
            <p class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $metrics['total_active'] }}</p>
            <span class="text-[10px] text-slate-400">Enrolled Staff</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Present (On-Time)</span>
            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['present'] }}</p>
            <span class="text-[10px] text-emerald-600">Within grace period</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Late Arrivals</span>
            <p class="text-xl font-black text-amber-500 mt-1">{{ $metrics['late'] }}</p>
            <span class="text-[10px] text-amber-500">Beyond 15 mins</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Half Days</span>
            <p class="text-xl font-black text-indigo-500 mt-1">{{ $metrics['half_day'] }}</p>
            <span class="text-[10px] text-indigo-500">Beyond 60 mins</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Absent / Unpunched</span>
            <p class="text-xl font-black text-rose-500 mt-1">{{ $metrics['absent'] }}</p>
            <span class="text-[10px] text-rose-500">No punch record</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Missing Punch-Out</span>
            <p class="text-xl font-black text-slate-700 dark:text-slate-300 mt-1">{{ $metrics['missing_punch_out'] }}</p>
            <span class="text-[10px] text-amber-600">Pending punch-out</span>
        </div>
    </div>

    <!-- Attendance Table & Quick Past Entry -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Attendance Roster -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Daily Attendance Roster ({{ $selectedDate }})</h3>
                <form method="GET" action="{{ route('hr-admin.attendance.index') }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee..."
                        class="px-3 py-1 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white">
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4">Shift</th>
                            <th class="py-3 px-4">Punch In</th>
                            <th class="py-3 px-4">Punch Out</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @forelse($employees as $emp)
                            @php
                                $rec = $emp->attendanceRecords->first();
                                $statusVal = $rec ? ($rec->status instanceof \App\Enums\AttendanceStatus ? $rec->status->value : (string) $rec->status) : 'absent';
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition-colors">
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $emp->full_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $emp->employee_code }} &bull; {{ $emp->department }}</p>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    {{ $emp->shift ? substr($emp->shift->start_time, 0, 5) . ' - ' . substr($emp->shift->end_time, 0, 5) : 'General' }}
                                </td>
                                <td class="py-3 px-4 font-mono">
                                    {{ $rec && $rec->punch_in_at ? $rec->punch_in_at->format('H:i:s') : '—' }}
                                </td>
                                <td class="py-3 px-4 font-mono">
                                    {{ $rec && $rec->punch_out_at ? $rec->punch_out_at->format('H:i:s') : '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                        {{ $statusVal === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : '' }}
                                        {{ $statusVal === 'late' ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400' : '' }}
                                        {{ $statusVal === 'half_day' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}
                                        {{ $statusVal === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400' : '' }}">
                                        {{ str_replace('_', ' ', $statusVal) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if($rec)
                                        <a href="{{ route('hr-admin.attendance.correct', $rec->id) }}"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                            Correct
                                        </a>
                                    @else
                                        <span class="text-[10px] text-slate-400">Unpunched</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                    No active employees found matching criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-700/60">
                {{ $employees->links() }}
            </div>
        </div>

        <!-- Add Missing Past Attendance Form -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Past Attendance</h3>
                <p class="text-xs text-slate-400">Add an unpunched historical attendance record with mandatory audit explanation.</p>
            </div>

            <form method="POST" action="{{ route('hr-admin.attendance.store-manual') }}" class="space-y-3.5">
                @csrf

                <div>
                    <label for="employee_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Employee <span class="text-rose-500">*</span>
                    </label>
                    <select id="employee_id" name="employee_id" required
                        class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                        <option value="">Select Employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendance_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="attendance_date" name="attendance_date" max="{{ now()->toDateString() }}" required value="{{ $selectedDate }}"
                        class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Attendance Status <span class="text-rose-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}">{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="punch_in_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Punch In
                        </label>
                        <input type="time" id="punch_in_at" name="punch_in_at" value="09:00"
                            class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="punch_out_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Punch Out
                        </label>
                        <input type="time" id="punch_out_at" name="punch_out_at" value="18:00"
                            class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label for="correction_reason" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Reason for Manual Entry <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="correction_reason" name="correction_reason" rows="2" required minlength="5"
                        placeholder="e.g. Employee visited client on duty / biometric device offline."
                        class="mt-1 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-1">
                    <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
                        Save Past Attendance Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
