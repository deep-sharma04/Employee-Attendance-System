@extends('layouts.app')

@section('title', 'Employee Directory')
@section('page-title', 'Employee Management')

@section('header-actions')
    <a href="{{ route('hr-admin.employees.create') }}"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-xs transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add New Employee
    </a>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('created_employee_credentials'))
        @php $creds = session('created_employee_credentials'); @endphp
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-5 backdrop-blur-md">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-emerald-300">🎉 Employee Account Provisioned Successfully</h4>
                    <p class="text-xs text-emerald-200/80">
                        Please securely share these initial credentials with <strong>{{ $creds['name'] }}</strong> ({{ $creds['code'] }}).
                    </p>
                    <div class="mt-3 flex flex-wrap gap-4 text-xs font-mono bg-slate-900/60 p-3 rounded-xl border border-emerald-500/20">
                        <div><span class="text-slate-400">Username:</span> <strong class="text-white">{{ $creds['username'] }}</strong></div>
                        <div><span class="text-slate-400">Temporary Password:</span> <strong class="text-emerald-400">{{ $creds['temporary_password'] }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs">
        <form method="GET" action="{{ route('hr-admin.employees.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5">
                <label for="search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by name, code, email, designation..."
                        class="block w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <div class="sm:col-span-3">
                <label for="department" class="sr-only">Department</label>
                <select id="department" name="department"
                    class="block w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ ($filters['department'] ?? '') === $dept ? 'selected' : '' }}>
                            {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status"
                    class="block w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit"
                    class="w-full py-2 px-3 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl transition-all">
                    Filter
                </button>
                @if(!empty($filters['search']) || !empty($filters['department']) || !empty($filters['status']))
                    <a href="{{ route('hr-admin.employees.index') }}"
                        class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
                        title="Clear filters">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Employee Listing Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4">Department & Role</th>
                        <th class="py-3 px-4">Assigned Shift</th>
                        <th class="py-3 px-4">Monthly Salary</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-xs text-slate-700 dark:text-slate-300">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('hr-admin.employees.show', $emp->id) }}" class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $emp->full_name }}
                                        </a>
                                        <p class="text-[11px] text-slate-400">{{ $emp->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-600 dark:text-slate-300">
                                {{ $emp->employee_code }}
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ $emp->designation }}</p>
                                <p class="text-[11px] text-slate-400">{{ $emp->department }}</p>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $emp->shift?->name ?? 'General Day Shift' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-white">
                                ₹{{ number_format((float)$emp->monthly_salary, 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    @if($emp->status?->value === 'active' || $emp->status === 'active') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400
                                    @elseif($emp->status?->value === 'inactive' || $emp->status === 'inactive') bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400
                                    @elseif($emp->status?->value === 'terminated' || $emp->status === 'terminated') bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400
                                    @else bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 @endif">
                                    {{ $emp->status?->label() ?? ucfirst((string)$emp->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('hr-admin.employees.show', $emp->id) }}"
                                        class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="View Profile">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('hr-admin.employees.edit', $emp->id) }}"
                                        class="p-1.5 text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Edit Employee">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Employees Found</p>
                                    <p class="text-xs text-slate-400">Get started by creating your first employee profile or adjusting your search filters.</p>
                                    <a href="{{ route('hr-admin.employees.create') }}"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition-all">
                                        Add New Employee
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700/60">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
