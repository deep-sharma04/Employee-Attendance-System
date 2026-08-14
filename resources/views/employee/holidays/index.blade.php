@extends('layouts.app')

@section('title', 'Holiday Calendar (' . $selectedYear . ')')
@section('page-title', 'Company Holiday Calendar')

@section('header-actions')
    <form method="GET" action="{{ route('employee.holidays.index') }}" class="flex items-center gap-2">
        <label for="year" class="sr-only">Select Year</label>
        <select id="year" name="year" onchange="this.form.submit()"
            class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold focus:border-indigo-500">
            @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>
                    Year {{ $year }}
                </option>
            @endforeach
        </select>
    </form>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-6 text-white shadow-md flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Official Holidays for {{ $selectedYear }}</h2>
            <p class="text-xs text-indigo-100 mt-1">Official non-working company holidays. These days are not deducted from your annual leave quota.</p>
        </div>
        <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl text-center">
            <span class="text-2xl font-black">{{ $holidays->count() }}</span>
            <span class="block text-[10px] uppercase font-bold tracking-wider text-indigo-200">Total Holidays</span>
        </div>
    </div>

    <!-- Holidays Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($holidays as $holiday)
            @php
                $isPast = $holiday->holiday_date->isPast() && !$holiday->holiday_date->isToday();
                $isToday = $holiday->holiday_date->isToday();
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center gap-4 transition-all hover:shadow-md {{ $isPast ? 'opacity-65 bg-slate-50/50 dark:bg-slate-800/40' : '' }}">
                <div class="flex-shrink-0 w-14 h-14 rounded-2xl {{ $isToday ? 'bg-amber-500 text-white' : ($isPast ? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400') }} flex flex-col items-center justify-center font-bold text-center">
                    <span class="text-[10px] uppercase tracking-wider font-semibold">{{ $holiday->holiday_date->format('M') }}</span>
                    <span class="text-lg leading-tight font-black">{{ $holiday->holiday_date->format('d') }}</span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $holiday->name }}</h3>
                        @if($isToday)
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400 animate-pulse">
                                Today
                            </span>
                        @elseif($isPast)
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                Passed
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                Upcoming
                            </span>
                        @endif
                    </div>

                    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5">
                        {{ $holiday->holiday_date->format('l') }}
                    </p>

                    @if($holiday->description)
                        <p class="text-[11px] text-slate-400 truncate mt-1">
                            {{ $holiday->description }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No declared holidays found for year {{ $selectedYear }}.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
