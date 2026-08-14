@extends('layouts.app')

@section('title', 'Generate Monthly Payroll')
@section('page-title', 'Generate & Calculate Monthly Payroll')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Run Monthly Payroll Engine</h2>
            <p class="text-xs text-slate-500">Calculate Daily Salary, LOP deductions, holiday bridging, and net pay.</p>
        </div>
        <a href="{{ route('hr-admin.payroll.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
            &larr; Back to Payroll
        </a>
    </div>

    <!-- Calculation Engine Guidelines -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 space-y-2">
        <h4 class="text-xs font-bold text-indigo-950 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Automated Payroll Rules & Statutory Provisions:
        </h4>
        <ul class="text-xs text-indigo-900 list-disc list-inside space-y-1">
            <li><span class="font-semibold">Daily Salary Divisor:</span> Computed as <code class="font-mono bg-indigo-100 px-1 py-0.5 rounded">Monthly Salary / 30</code>.</li>
            <li><span class="font-semibold">Loss of Pay (LOP) Aggregation:</span> Combines direct absences, late conversions (3 late = 1 absent), half-day conversions (2 half days = 1 absent), and unapproved holiday bridging.</li>
            <li><span class="font-semibold">Approved Leave Protection:</span> Approved casual/medical leaves are never penalized as LOP.</li>
            <li><span class="font-semibold">Workflow Governance:</span> Draft &rarr; Under Review (HR Admin) &rarr; Super Admin Approved &rarr; Finalized & Locked.</li>
        </ul>
    </div>

    <!-- Generation Form -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <form method="POST" action="{{ route('hr-admin.payroll.generate') }}" class="space-y-5">
            @csrf

            <!-- Payroll Year & Month -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="year" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Payroll Year <span class="text-rose-500">*</span>
                    </label>
                    <select name="year" id="year" required
                        class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                            <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="month" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Payroll Month <span class="text-rose-500">*</span>
                    </label>
                    <select name="month" id="month" required
                        class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Scope Selection (All active vs single) -->
            <div>
                <label for="employee_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Target Workforce Scope <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <select name="employee_id" id="employee_id"
                    class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- All Active Employees (Batch Run) --</option>
                    @foreach($activeEmployees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code }}) — {{ $emp->department }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Leave empty to calculate payroll for all {{ $activeEmployees->count() }} active workforce members at once.</p>
            </div>

            <!-- Submit Form Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('hr-admin.payroll.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Calculate & Generate Drafts
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
