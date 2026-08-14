@extends('layouts.app')

@section('title', 'New Weekly Timesheet')
@section('page-title', 'Create Project Timesheet')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Create Timesheet</h2>
            <p class="text-xs text-slate-500 mt-0.5">Select reporting week to initiate a project effort timesheet</p>
        </div>
        <a href="{{ route('employee.timesheets.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Back to Timesheets
        </a>
    </div>

    <form method="POST" action="{{ route('employee.timesheets.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf
        <input type="hidden" name="period_type" value="weekly">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="start_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Week Start Date (Monday) <span class="text-rose-500">*</span>
                </label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $defaultStartDate) }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('start_date')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="end_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Week End Date (Sunday) <span class="text-rose-500">*</span>
                </label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $defaultEndDate) }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('end_date')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 flex items-start gap-3">
            <svg class="h-5 w-5 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="text-xs text-indigo-900 space-y-1">
                <span class="font-bold">Project Timesheet Guidelines</span>
                <p class="text-indigo-700">Once created, you can log individual work entries by project and task across each day in the selected week before submitting to your Manager or Team Lead.</p>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('employee.timesheets.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Create Timesheet Draft
            </button>
        </div>
    </form>
</div>
@endsection
