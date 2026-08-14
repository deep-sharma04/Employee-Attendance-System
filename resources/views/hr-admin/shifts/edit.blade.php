@extends('layouts.app')

@section('title', 'Edit Shift: ' . $shift->name)
@section('page-title', 'Edit Work Shift Schedule')

@section('header-actions')
    <a href="{{ route('hr-admin.shifts.index') }}"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs transition-all">
        &larr; Back to Shifts
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('hr-admin.shifts.update', $shift->id) }}" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-5">
        @csrf
        @method('PUT')

        <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Shift Schedule Configuration</h3>
            <p class="text-xs text-slate-400">Modify timings and grace thresholds for shift schedule.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Shift Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" required value="{{ old('name', $shift->name) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Shift Code <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="code" name="code" required value="{{ old('code', $shift->code) }}"
                    class="mt-1.5 block w-full uppercase font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('code') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="start_time" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Start Time <span class="text-rose-500">*</span>
                </label>
                <input type="time" id="start_time" name="start_time" required value="{{ old('start_time', substr($shift->start_time, 0, 5)) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('start_time') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="end_time" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    End Time <span class="text-rose-500">*</span>
                </label>
                <input type="time" id="end_time" name="end_time" required value="{{ old('end_time', substr($shift->end_time, 0, 5)) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('end_time') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="grace_period_minutes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Grace Period (Minutes) <span class="text-rose-500">*</span>
                </label>
                <input type="number" min="0" max="120" id="grace_period_minutes" name="grace_period_minutes" required value="{{ old('grace_period_minutes', $shift->grace_period_minutes) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('grace_period_minutes') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="half_day_threshold_minutes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Half-Day Threshold (Minutes) <span class="text-rose-500">*</span>
                </label>
                <input type="number" min="15" max="360" id="half_day_threshold_minutes" name="half_day_threshold_minutes" required value="{{ old('half_day_threshold_minutes', $shift->half_day_threshold_minutes) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('half_day_threshold_minutes') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Working Days Selector -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Scheduled Working Days <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($allDays as $day)
                    @php $checked = in_array($day, old('working_days', is_array($shift->working_days) ? $shift->working_days : [])); @endphp
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-all text-xs text-slate-800 dark:text-slate-200">
                        <input type="checkbox" name="working_days[]" value="{{ $day }}" {{ $checked ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ ucfirst($day) }}</span>
                    </label>
                @endforeach
            </div>
            @error('working_days') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
        </div>

        <!-- Active Toggle -->
        <div class="pt-2">
            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 font-semibold">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $shift->is_active) ? 'checked' : '' }}
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Active shift schedule</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700/60">
            <a href="{{ route('hr-admin.shifts.index') }}"
                class="px-5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/30 transition-all">
                Save & Update Shift
            </button>
        </div>
    </form>
</div>
@endsection
