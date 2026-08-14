@extends('layouts.app')

@section('title', 'Leave Types & Quota Allocations')
@section('page-title', 'Leave Policies & Allocations')

@section('content')
<div class="space-y-8">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('hr-admin.leaves.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Back to Leave Approvals
        </a>
    </div>

    <!-- Section 1: Leave Types Table -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700/60 shadow-xs p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Active Leave Types & Quotas</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Define company annual quotas for Casual, Medical, and special leave categories.</p>
            </div>

            <!-- Create Type Form Modal / Inline Trigger -->
            <button type="button" onclick="document.getElementById('createTypeSection').classList.toggle('hidden')"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs transition-colors">
                + Create Leave Type
            </button>
        </div>

        <!-- Inline Create Leave Type Form (hidden by default) -->
        <div id="createTypeSection" class="hidden p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">New Leave Type Definition</h4>
            <form method="POST" action="{{ route('hr-admin.leaves.types.store') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Name</label>
                    <input type="text" name="name" required placeholder="e.g. Maternity Leave"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Slug (Identifier)</label>
                    <input type="text" name="slug" required placeholder="e.g. maternity_leave"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Annual Quota (Days)</label>
                    <input type="number" step="0.5" name="annual_quota" required value="12.0"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors">
                        Save Type
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Slug</th>
                        <th class="px-5 py-3">Annual Quota</th>
                        <th class="px-5 py-3">Doc Required</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @forelse($leaveTypes as $type)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">{{ $type->name }}</td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-slate-500">{{ $type->slug->value ?? $type->slug }}</td>
                            <td class="px-5 py-3.5 font-bold text-indigo-600 dark:text-indigo-400">{{ $type->annual_quota }} Days</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $type->requires_document ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $type->requires_document ? 'Required' : 'Optional' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $type->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">No leave types found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Quota Allocation Form & Employee Balances -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700/60 shadow-xs p-6 space-y-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Allocate Employee Leave Quota (Cycle {{ $currentYear }})</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Assign or adjust annual leave days for individual staff members.</p>
        </div>

        <form method="POST" action="{{ route('hr-admin.leaves.allocation.store') }}" class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Employee</label>
                <select name="employee_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Leave Type</label>
                <select name="leave_type_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }} (Default: {{ $type->annual_quota }}d)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Year & Days</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" name="year" value="{{ $currentYear }}" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                    <input type="number" step="0.5" name="allocated_days" value="12.0" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white">
                </div>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs transition-colors">
                    Save Allocation
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
