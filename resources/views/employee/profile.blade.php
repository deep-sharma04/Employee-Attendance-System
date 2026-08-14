@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'Employee Self-Service Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(!$employee)
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 text-center text-xs text-amber-300">
            <p class="font-bold">No employee profile is linked to your user account.</p>
            <p class="text-slate-400 mt-1">Please contact your HR administrator to provision your full employee profile.</p>
        </div>
    @else
        <!-- Top Profile Banner -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-500/20">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $employee->full_name }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400">
                            {{ $employee->status?->label() ?? 'Active' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{{ $employee->employee_code }}</span> &bull; {{ $employee->designation }} &bull; {{ $employee->department }}
                    </p>
                </div>
            </div>

            <!-- 4 Metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-700/60 text-xs">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assigned Shift</span>
                    <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->shift?->name ?? 'General Day Shift' }}</p>
                    <p class="text-[10px] text-slate-400">{{ substr($employee->shift?->start_time ?? '09:00', 0, 5) }} - {{ substr($employee->shift?->end_time ?? '18:00', 0, 5) }}</p>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Grace Period</span>
                    <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->shift?->grace_period_minutes ?? 15 }} Minutes</p>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Joining Date</span>
                    <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ optional($employee->joining_date)->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Leave Balance</span>
                    <p class="text-base font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">
                        {{ $employee->leaveBalances->sum('remaining_days') }} <span class="text-xs font-normal text-slate-400">Days</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- 2 Column Details -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Personal Information (Read-Only) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">My Information</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium">Full Name</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Official Email</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Contact Phone</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Gender</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5 capitalize">{{ $employee->gender }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Portal Username</dt>
                        <dd class="font-semibold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $user->username }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Account Status</dt>
                        <dd class="font-semibold text-emerald-500 mt-0.5">Active & Verified</dd>
                    </div>
                </dl>
            </div>

            <!-- Protected Bank Details (Masked) (Task T051) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Disbursement Bank Account</h3>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-md font-semibold">Protected</span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium">Bank Name</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->bank_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Account Number</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">
                            •••• •••• {{ substr($employee->account_number, -4) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">IFSC Code</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">{{ $employee->ifsc_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">PAN Number</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">
                            {{ substr($employee->pan_number, 0, 2) }}••••{{ substr($employee->pan_number, -2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Leave Balances -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">My Leave Quota ({{ date('Y') }})</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse($employee->leaveBalances as $balance)
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700/40 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $balance->leaveType?->name ?? 'Leave' }}</p>
                            <p class="text-[10px] text-slate-400">Allocated: {{ $balance->allocated_days }} &bull; Used: {{ $balance->used_days }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $balance->remaining_days }}</span>
                            <span class="text-[10px] text-slate-400 block">Days Left</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No leave quotas allocated.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
