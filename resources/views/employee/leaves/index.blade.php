@extends('layouts.app')

@section('title', 'My Leave Balances & Requests')
@section('page-title', 'My Leave Portal')

@section('content')
<div class="space-y-6">
    <!-- Top Action Bar & Quota Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Annual Leave Quotas (Cycle {{ $currentYear }})</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Available leave balances expire at cycle end without carry-forward.</p>
        </div>
        <a href="{{ route('employee.leaves.create') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Apply for Leave
        </a>
    </div>

    <!-- Quota Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($balances as $balance)
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        {{ $balance->leaveType->name ?? 'Leave Type' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                        Allocated: {{ $balance->allocated_days }}d
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $balance->remaining_days }}</span>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">days available</span>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700/40 pt-2.5">
                    <span>Used so far: <strong class="text-slate-800 dark:text-slate-200">{{ $balance->used_days }}d</strong></span>
                    <span>Year {{ $currentYear }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 text-center">
                <p class="text-xs text-slate-500">No leave quotas allocated for cycle {{ $currentYear }} yet. Please contact HR.</p>
            </div>
        @endforelse
    </div>

    <!-- Leave Request History Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Leave Application History</h4>
            <span class="text-xs text-slate-500">Total: {{ $leaveRequests->total() ?? count($leaveRequests) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">Leave Type</th>
                        <th class="px-6 py-3.5">Duration & Dates</th>
                        <th class="px-6 py-3.5">Days</th>
                        <th class="px-6 py-3.5">Reason</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @forelse($leaveRequests as $req)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">#{{ $req->id }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $req->leaveType->name ?? 'Leave' }}
                                @if($req->is_half_day)
                                    <span class="block text-[10px] text-indigo-500 font-normal">({{ ucfirst(str_replace('_', ' ', $req->half_day_type ?? 'half day')) }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-800 dark:text-white">{{ $req->start_date }}</span>
                                @if($req->start_date !== $req->end_date)
                                    <span class="text-slate-400"> to </span>
                                    <span class="font-medium text-slate-800 dark:text-white">{{ $req->end_date }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">{{ $req->total_days }}d</td>
                            <td class="px-6 py-4 max-w-xs truncate" title="{{ $req->reason }}">{{ $req->reason }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $st = $req->status->value ?? $req->status;
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase
                                    {{ $st === 'approved' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                    {{ $st === 'pending' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                    {{ $st === 'rejected' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}
                                    {{ $st === 'cancelled' ? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' : '' }}">
                                    {{ $st }}
                                </span>
                                @if($st === 'rejected' && $req->rejection_reason)
                                    <span class="block text-[10px] text-rose-500 mt-0.5 truncate" title="{{ $req->rejection_reason }}">Reason: {{ $req->rejection_reason }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($st === 'pending')
                                    <form method="POST" action="{{ route('employee.leaves.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending leave request?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-300 text-xs font-bold transition-all">
                                            Cancel
                                        </button>
                                    </form>
                                @elseif($st === 'approved')
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold" title="Approved leave cannot be cancelled directly. Contact HR.">
                                        Approved (Active)
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No leave applications found. Click "Apply for Leave" above to submit a request.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($leaveRequests, 'hasPages') && $leaveRequests->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700/60">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
