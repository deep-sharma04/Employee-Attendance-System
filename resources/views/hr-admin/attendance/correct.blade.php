@extends('layouts.app')

@section('title', 'Manual Attendance Correction: ' . $record->employee->full_name)
@section('page-title', 'Manual Attendance Record Correction')

@section('header-actions')
    <a href="{{ route('hr-admin.attendance.index', ['date' => $record->attendance_date->format('Y-m-d')]) }}"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs transition-all">
        &larr; Back to Monitoring
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Employee Summary Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center gap-4">
        <div class="h-12 w-12 rounded-2xl bg-indigo-600 text-white font-bold flex items-center justify-center text-base shadow-xs">
            {{ substr($record->employee->first_name, 0, 1) }}
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $record->employee->full_name }}</h3>
            <p class="text-xs text-slate-500 font-mono">{{ $record->employee->employee_code }} &bull; {{ $record->employee->department }} &bull; {{ $record->employee->designation }}</p>
            <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                Shift: {{ $record->employee->shift?->name ?? 'General Day Shift' }} ({{ substr($record->employee->shift?->start_time ?? '09:00', 0, 5) }} - {{ substr($record->employee->shift?->end_time ?? '18:00', 0, 5) }})
            </p>
        </div>
    </div>

    <!-- Correction Form -->
    <form method="POST" action="{{ route('hr-admin.attendance.store-correction', $record->id) }}" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-5">
        @csrf

        <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Correction Details — {{ $record->attendance_date->format('M d, Y (l)') }}</h3>
            <p class="text-xs text-slate-400">Manual corrections override raw biometric punch metrics and are permanently audited.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Corrected Status <span class="text-rose-500">*</span>
                </label>
                <select id="status" name="status" required
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                    @foreach($statuses as $st)
                        @php
                            $currentVal = $record->status instanceof \App\Enums\AttendanceStatus ? $record->status->value : (string) $record->status;
                        @endphp
                        <option value="{{ $st->value }}" {{ old('status', $currentVal) === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="total_hours" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Total Hours Worked
                </label>
                <input type="number" step="0.25" min="0" max="24" id="total_hours" name="total_hours" value="{{ old('total_hours', $record->total_hours) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                @error('total_hours') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="punch_in_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Punch In Time (HH:MM)
                </label>
                <input type="time" id="punch_in_at" name="punch_in_at" value="{{ old('punch_in_at', $record->punch_in_at ? $record->punch_in_at->format('H:i') : '') }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                @error('punch_in_at') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="punch_out_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Punch Out Time (HH:MM)
                </label>
                <input type="time" id="punch_out_at" name="punch_out_at" value="{{ old('punch_out_at', $record->punch_out_at ? $record->punch_out_at->format('H:i') : '') }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500">
                @error('punch_out_at') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="correction_reason" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Mandatory Correction Reason <span class="text-rose-500">*</span>
            </label>
            <textarea id="correction_reason" name="correction_reason" rows="3" required minlength="5" maxlength="255"
                placeholder="Explain why this attendance record is being manually overridden (e.g. Employee visited client on duty / biometric terminal hardware synchronization error)."
                class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('correction_reason', $record->correction_reason) }}</textarea>
            @error('correction_reason') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
        </div>

        <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200/60 dark:border-amber-700/40 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <span class="font-bold">Audit Trail Notice:</span>
                This manual change will record your user account as the modifier with a cryptographic timestamp and before/after comparison snapshots.
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700/60">
            <a href="{{ route('hr-admin.attendance.index', ['date' => $record->attendance_date->format('Y-m-d')]) }}"
                class="px-5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/30 transition-all">
                Save & Apply Correction
            </button>
        </div>
    </form>
</div>
@endsection
