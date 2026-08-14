@extends('layouts.app')

@section('title', 'Shift Schedules')
@section('page-title', 'Shift Management')

@section('header-actions')
    <a href="{{ route('hr-admin.shifts.create') }}"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-xs transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add New Shift
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($shifts as $shift)
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $shift->name }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $shift->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $shift->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 font-semibold">{{ $shift->code }}</span>
                        </div>
                    </div>

                    <!-- Shift Time Metrics -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/40 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] font-semibold uppercase text-slate-400">Timings</span>
                            <p class="font-bold text-slate-900 dark:text-white mt-0.5">
                                {{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold uppercase text-slate-400">Assigned Staff</span>
                            <p class="font-bold text-slate-900 dark:text-white mt-0.5">
                                {{ $shift->employees_count ?? 0 }} Employees
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold uppercase text-slate-400">Grace Period</span>
                            <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">
                                {{ $shift->grace_period_minutes }} Mins
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold uppercase text-slate-400">Half-Day Threshold</span>
                            <p class="font-medium text-slate-700 dark:text-slate-300 mt-0.5">
                                {{ $shift->half_day_threshold_minutes }} Mins
                            </p>
                        </div>
                    </div>

                    <!-- Working Days Badges -->
                    <div>
                        <span class="text-[10px] font-semibold uppercase text-slate-400 block mb-1.5">Scheduled Working Days</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                @php $isDayActive = is_array($shift->working_days) && in_array($day, $shift->working_days); @endphp
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium {{ $isDayActive ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 font-semibold' : 'text-slate-300 dark:text-slate-600 line-through' }}">
                                    {{ ucfirst(substr($day, 0, 3)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Shift Actions -->
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <form method="POST" action="{{ route('hr-admin.shifts.toggle-status', $shift->id) }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold {{ $shift->is_active ? 'text-rose-600 dark:text-rose-400 hover:underline' : 'text-emerald-600 dark:text-emerald-400 hover:underline' }}">
                            {{ $shift->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    <a href="{{ route('hr-admin.shifts.edit', $shift->id) }}"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition-all">
                        Edit Schedule
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No shifts configured.</p>
                <a href="{{ route('hr-admin.shifts.create') }}" class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-xl">
                    Create General Shift
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
