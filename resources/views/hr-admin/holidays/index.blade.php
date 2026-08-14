@extends('layouts.app')

@section('title', 'Holiday Calendar (' . $selectedYear . ')')
@section('page-title', 'Company Holiday Calendar')

@section('header-actions')
    <div class="flex items-center gap-2">
        <form method="GET" action="{{ route('hr-admin.holidays.index') }}" class="flex items-center gap-2">
            <label for="year" class="sr-only">Year</label>
            <select id="year" name="year" onchange="this.form.submit()"
                class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-semibold focus:border-indigo-500">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>
                        Year {{ $year }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Holiday Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
        <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Declare Official Holiday</h3>
            <p class="text-xs text-slate-400">Declared holidays are excluded from employee leave quota balance deduction.</p>
        </div>

        <form method="POST" action="{{ route('hr-admin.holidays.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="holiday_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Holiday Date <span class="text-rose-500">*</span>
                </label>
                <input type="date" id="holiday_date" name="holiday_date" required value="{{ old('holiday_date', date('Y-m-d')) }}"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('holiday_date') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Holiday Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}"
                    placeholder="e.g. Independence Day or Diwali"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('name') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Description / Category
                </label>
                <input type="text" id="description" name="description" value="{{ old('description') }}"
                    placeholder="e.g. National Gazetted Holiday"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('description') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 font-semibold">
                    <input type="checkbox" name="is_recurring_yearly" value="1" {{ old('is_recurring_yearly', true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Recurs every year on the same date</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
                    Add Holiday to Calendar
                </button>
            </div>
        </form>
    </div>

    <!-- Holidays List Table -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Declared Holidays for Year {{ $selectedYear }} ({{ $holidays->count() }})</h3>
            <span class="text-[11px] text-slate-400 font-medium">
                {{ $upcomingCount }} Upcoming in System
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-4">Date & Day</th>
                        <th class="py-3 px-4">Holiday Name</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($holidays as $holiday)
                        @php
                            $isPast = $holiday->holiday_date->isPast();
                            $isToday = $holiday->holiday_date->isToday();
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition-colors {{ $isPast && !$isToday ? 'opacity-70' : '' }}">
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-900 dark:text-white">
                                    {{ $holiday->holiday_date->format('M d, Y') }}
                                </p>
                                <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                                    {{ $holiday->holiday_date->format('l') }}
                                </p>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $holiday->name }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                                {{ $holiday->description ?? 'Official Declared Holiday' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $holiday->is_recurring_yearly ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $holiday->is_recurring_yearly ? 'Annual Recurring' : 'Single Occurrence' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form method="POST" action="{{ route('hr-admin.holidays.destroy', $holiday->id) }}" onsubmit="return confirm('Are you sure you want to remove {{ $holiday->name }} from the holiday calendar?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-slate-400">
                                No declared holidays found for year {{ $selectedYear }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
