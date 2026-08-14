@extends('layouts.app')

@section('title', $employee->full_name . ' (' . $employee->employee_code . ')')
@section('page-title', 'Employee Profile Summary')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('hr-admin.employees.index') }}"
            class="px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs transition-all">
            &larr; Directory
        </a>
        <a href="{{ route('hr-admin.employees.edit', $employee->id) }}"
            class="px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-xs transition-all">
            Edit Profile
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Profile Banner -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-500/20">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $employee->full_name }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                            @if($employee->status?->value === 'active' || $employee->status === 'active') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400
                            @elseif($employee->status?->value === 'inactive' || $employee->status === 'inactive') bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400
                            @elseif($employee->status?->value === 'terminated' || $employee->status === 'terminated') bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400
                            @else bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 @endif">
                            {{ $employee->status?->label() ?? ucfirst((string)$employee->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{{ $employee->employee_code }}</span> &bull; {{ $employee->designation }} &bull; {{ $employee->department }}
                    </p>
                </div>
            </div>

            <!-- Quick Status Change Form -->
            <form method="POST" action="{{ route('hr-admin.employees.update-status', $employee->id) }}" class="flex items-center gap-2 w-full sm:w-auto">
                @csrf
                <select name="status" class="py-1.5 px-3 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:border-indigo-500">
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ ($employee->status?->value ?? (string)$employee->status) === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
                <input type="text" name="status_change_reason" required placeholder="Reason for status change..."
                    class="py-1.5 px-3 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl transition-all">
                    Update Status
                </button>
            </form>
        </div>

        <!-- 4 Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-700/60">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Monthly Gross</span>
                <p class="text-base font-bold text-slate-900 dark:text-white mt-0.5">₹{{ number_format((float)$employee->monthly_salary, 2) }}</p>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assigned Shift</span>
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ $employee->shift?->name ?? 'General Day Shift' }}</p>
                <p class="text-[10px] text-slate-400">{{ substr($employee->shift?->start_time ?? '09:00', 0, 5) }} - {{ substr($employee->shift?->end_time ?? '18:00', 0, 5) }}</p>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Joining Date</span>
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ optional($employee->joining_date)->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Leave Balance</span>
                <p class="text-base font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">
                    {{ $employee->leaveBalances->sum('remaining_days') }} <span class="text-xs font-normal text-slate-400">Days</span>
                </p>
            </div>
        </div>
    </div>

    <!-- 2 Column Details: Personal / Bank Information & Leave Balances -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1: Personal & Bank Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal & Employment Information -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Personal & Employment Details</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
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
                        <dt class="text-slate-400 font-medium">Date of Birth</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ optional($employee->date_of_birth)->format('M d, Y') ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Portal Username</dt>
                        <dd class="font-semibold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $employee->user?->username ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Last Portal Login</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ optional($employee->user?->last_login_at)->format('M d, Y H:i') ?? 'Never' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Bank & Statutory Details (T051) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank & Statutory Details (Confidential)</h3>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-md font-semibold">Protected Data</span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium">Bank Name</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $employee->bank_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Bank Account Number</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">{{ $employee->account_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">IFSC Code</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">{{ $employee->ifsc_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">PAN Number</dt>
                        <dd class="font-semibold font-mono text-slate-900 dark:text-white mt-0.5">{{ $employee->pan_number }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Column 2: Leave Balances & Policies -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Leave Balance Summary ({{ date('Y') }})</h3>
                <div class="space-y-3">
                    @forelse($employee->leaveBalances as $balance)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700/40 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $balance->leaveType?->name ?? 'Leave' }}</p>
                                <p class="text-[10px] text-slate-400">Allocated: {{ $balance->allocated_days }} &bull; Used: {{ $balance->used_days }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ $balance->remaining_days }}</span>
                                <span class="text-[10px] text-slate-400 block">Left</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No leave quotas allocated yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Records -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Recent Attendance History (Last 10 Days)</h3>
            <a href="{{ route('hr-admin.attendance.index') }}?search={{ $employee->employee_code }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                View Full Attendance &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 text-slate-400">
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Punch In</th>
                        <th class="py-2.5 px-3">Punch Out</th>
                        <th class="py-2.5 px-3">Total Hours</th>
                        <th class="py-2.5 px-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($employee->attendanceRecords as $att)
                        <tr>
                            <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ optional($att->attendance_date)->format('M d, Y') }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $att->punch_in ?? '—' }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $att->punch_out ?? '—' }}</td>
                            <td class="py-2.5 px-3 font-medium">{{ $att->total_working_hours ? $att->total_working_hours . ' hrs' : '—' }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $att->status?->label() ?? ucfirst((string)$att->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">No attendance records logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employee Documents Repository (T108) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Employee Documents & Verification</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('hr-admin.documents.create', ['employee_id' => $employee->id]) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    + Upload New Document
                </a>
                <a href="{{ route('hr-admin.documents.index', ['employee_id' => $employee->id]) }}" class="text-xs font-semibold text-slate-500 hover:underline">
                    All Files &rarr;
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 text-slate-400">
                        <th class="py-2.5 px-3">Title & Classification</th>
                        <th class="py-2.5 px-3">File Name</th>
                        <th class="py-2.5 px-3">Size</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($employee->documents as $doc)
                        <tr>
                            <td class="py-2.5 px-3">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $doc->title }}</span>
                                <div class="text-[10px] text-slate-400">{{ $doc->documentType?->name ?? 'Document' }}</div>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $doc->file_name }}</td>
                            <td class="py-2.5 px-3 text-slate-500">{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                            <td class="py-2.5 px-3">
                                @php
                                    $statusValue = $doc->status instanceof \App\Enums\DocumentStatus ? $doc->status->value : (string) $doc->status;
                                @endphp
                                @if($statusValue === 'verified')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Verified
                                    </span>
                                @elseif($statusValue === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $doc->rejection_reason }}">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending Verification
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('hr-admin.documents.view', $doc->id) }}" target="_blank" class="text-indigo-600 hover:underline font-medium">View</a>
                                    <a href="{{ route('hr-admin.documents.download', $doc->id) }}" class="text-slate-500 hover:underline">Download</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">No documents uploaded for this employee yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
