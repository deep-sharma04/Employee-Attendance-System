@extends('layouts.app')

@section('title', 'Leave Management & Approvals')
@section('page-title', 'Leave Management Center')

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Approvals</p>
                <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $stats['pending_count'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-slate-400">Requires action</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Requests</p>
                <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['approved_count'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Synced to Attendance</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rejected Requests</p>
                <h3 class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $stats['rejected_count'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-rose-500">With logged reason</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Applications</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['total_requests'] ?? 0 }}</h3>
                <a href="{{ route('hr-admin.leaves.types') }}" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Manage Quotas &rarr;
                </a>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/60 shadow-xs">
        <form method="GET" action="{{ route('hr-admin.leaves.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status Filter</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:outline-hidden">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Approvals</option>
                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department</label>
                <select name="department" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:outline-hidden">
                    <option value="">-- All Departments --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ $departmentFilter === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Employee</label>
                <select name="employee_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:outline-hidden">
                    <option value="">-- All Employees --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $employeeFilter == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors">
                    Filter Queue
                </button>
                <a href="{{ route('hr-admin.leaves.index') }}" class="py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Main Leave Queue Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Leave Applications & Approval Queue</h4>
            <span class="text-xs text-slate-500">Showing {{ count($leaveRequests) }} requests</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-3.5">Employee</th>
                        <th class="px-6 py-3.5">Leave Type</th>
                        <th class="px-6 py-3.5">Date Span</th>
                        <th class="px-6 py-3.5">Days</th>
                        <th class="px-6 py-3.5">Reason</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @forelse($leaveRequests as $req)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $req->employee->full_name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $req->employee->employee_code ?? '' }} • {{ $req->employee->department ?? '' }}</div>
                            </td>
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
                            <td class="px-6 py-4 max-w-xs">
                                <p class="truncate text-slate-700 dark:text-slate-300" title="{{ $req->reason }}">{{ $req->reason }}</p>
                                @if($req->rejection_reason)
                                    <p class="text-[10px] text-rose-500 mt-0.5 truncate" title="Rejection Reason: {{ $req->rejection_reason }}">Rejected: {{ $req->rejection_reason }}</p>
                                @endif
                            </td>
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
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($st === 'pending')
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Approve Form -->
                                        <form method="POST" action="{{ route('hr-admin.leaves.approve', $req->id) }}" onsubmit="return confirm('Approve this leave request and sync {{ $req->total_days }} day(s) to attendance?');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-colors">
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Reject Trigger -->
                                        <button type="button" onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name ?? 'Employee') }}', '{{ $req->total_days }}')"
                                            class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-300 text-xs font-bold transition-colors">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400">
                                        Reviewed by {{ $req->reviewer->name ?? 'Admin' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No leave applications found matching your criteria.
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

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full border border-slate-200 dark:border-slate-700 shadow-xl space-y-5">
        <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modalTitle">Reject Leave Application</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Please provide a mandatory explanation for rejecting this request.</p>
        </div>

        <form id="rejectForm" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label for="rejection_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    Rejection Reason <span class="text-rose-500">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="State why this leave request cannot be approved at this time (min 5 characters)..."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeRejectModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs transition-colors">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(id, employeeName, days) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        const title = document.getElementById('modalTitle');

        form.action = `/hr-admin/leaves/${id}/reject`;
        title.innerText = `Reject Leave Application for ${employeeName} (${days} days)`;
        modal.classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endsection
