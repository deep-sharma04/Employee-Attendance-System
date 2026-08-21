@extends('layouts.app')

@section('title', 'Review Timesheet — ' . $timesheet->employee->full_name)
@section('page-title', 'Review Timesheet: ' . $timesheet->employee->full_name)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $timesheet->status?->badgeClass() }}">
                    {{ $timesheet->status?->label() }}
                </span>
                <span class="text-xs text-slate-400 font-mono uppercase">{{ $timesheet->period_type }} timesheet</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $timesheet->employee->full_name }} ({{ $timesheet->employee->employee_code }})
            </h1>
            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                <span>Period: <strong class="text-slate-800">{{ $timesheet->start_date->format('M d') }} — {{ $timesheet->end_date->format('M d, Y') }}</strong></span>
                <span>&bull;</span>
                <span>Total Effort: <strong class="text-indigo-600 font-bold text-sm">{{ $timesheet->total_hours }} Hours</strong></span>
                <span>&bull;</span>
                <span>Calculated Labor Cost: <strong class="text-emerald-700 font-bold text-sm">${{ number_format($totalLaborCost, 2) }}</strong></span>
            </p>
            @if($timesheet->first_submitted_at || $timesheet->resubmitted_at)
                <p class="text-[11px] text-slate-400 flex flex-wrap items-center gap-2">
                    @if($timesheet->first_submitted_at)
                        <span>First Submitted: <strong>{{ $timesheet->first_submitted_at->format('M d, Y H:i') }}</strong></span>
                    @endif
                    @if($timesheet->first_submitted_at && $timesheet->resubmitted_at)
                        <span>&bull;</span>
                    @endif
                    @if($timesheet->resubmitted_at)
                        <span>Last Resubmitted: <strong>{{ $timesheet->resubmitted_at->format('M d, Y H:i') }}</strong></span>
                    @endif
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('manager.timesheets.index') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                &larr; Back to Queue
            </a>

            @if($timesheet->status->value === 'submitted')
                <form method="POST" action="{{ route('manager.timesheets.approve', $timesheet) }}" onsubmit="return confirm('Approve this timesheet? Hours will be reconciled and locked.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm shadow-emerald-600/20 transition-all">
                        Approve Timesheet
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Review Decision Notice (if approved, rejected, or returned) -->
    @if($timesheet->status->value === 'approved')
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-start gap-3 text-emerald-900">
            <svg class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="space-y-0.5 text-xs">
                <h4 class="font-bold text-sm text-emerald-800">Approved by {{ $timesheet->approver?->name }} on {{ $timesheet->approved_at?->format('M d, Y H:i') }}</h4>
                <p class="text-emerald-700">Timesheet effort and calculated labor cost have been reconciled against projects.</p>
            </div>
        </div>
    @elseif($timesheet->status->value === 'rejected')
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-rose-900">
            <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="space-y-0.5 text-xs">
                <h4 class="font-bold text-sm text-rose-800">Rejected by {{ $timesheet->approver?->name }}</h4>
                <p class="text-rose-700"><strong>Reason:</strong> {{ $timesheet->rejection_reason }}</p>
            </div>
        </div>
    @elseif($timesheet->status->value === 'returned')
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-start gap-3 text-purple-900">
            <svg class="h-5 w-5 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div class="space-y-0.5 text-xs">
                <h4 class="font-bold text-sm text-purple-800">Returned for Employee Revisions</h4>
                <p class="text-purple-700"><strong>Remarks:</strong> {{ $timesheet->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <!-- Work Log Entries Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Itemized Work Log Entries</h3>
            <span class="text-xs font-semibold text-slate-500">Total Entries: {{ $timesheet->entries->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">Project</th>
                        <th class="px-5 py-3.5">Task</th>
                        <th class="px-5 py-3.5">Hours</th>
                        <th class="px-5 py-3.5">Billable</th>
                        <th class="px-5 py-3.5">Labor Cost</th>
                        <th class="px-5 py-3.5">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($timesheet->entries as $entry)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4 font-bold text-slate-900">
                                {{ $entry->entry_date->format('D, M d, Y') }}
                            </td>

                            <td class="px-5 py-4 font-semibold text-slate-800">
                                {{ $entry->project->name }}
                            </td>

                            <td class="px-5 py-4">
                                @if($entry->task)
                                    <span class="font-mono text-[10px] text-slate-400 font-bold uppercase">{{ $entry->task->task_code }}</span>
                                    <span class="block text-slate-800">{{ $entry->task->title }}</span>
                                @else
                                    <span class="text-slate-400 italic">General Project Effort</span>
                                @endif
                            </td>

                            <td class="px-5 py-4 font-bold text-indigo-600 text-sm">
                                {{ $entry->hours }} hrs
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $entry->is_billable ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $entry->is_billable ? 'Billable' : 'Non-billable' }}
                                </span>
                            </td>

                            <td class="px-5 py-4 font-semibold text-emerald-700">
                                ${{ number_format($entry->calculated_cost, 2) }}
                            </td>

                            <td class="px-5 py-4 text-slate-600 max-w-sm">
                                {{ $entry->description ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions Drawer (Return or Reject) -->
    @if($timesheet->status->value === 'submitted')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Return for Revisions -->
            <form method="POST" action="{{ route('manager.timesheets.return', $timesheet) }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                @csrf
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-purple-900 uppercase tracking-wider">Return for Revisions</h3>
                    <p class="text-xs text-slate-500">Unlocks the timesheet draft for the employee to correct hours or descriptions.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Feedback / Requested Changes *</label>
                    <textarea name="rejection_reason" rows="2" required placeholder="Describe what requires correction before approval..."
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white hover:bg-purple-700 transition-colors">
                    Return to Employee
                </button>
            </form>

            <!-- Reject Timesheet -->
            <form method="POST" action="{{ route('manager.timesheets.reject', $timesheet) }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                @csrf
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-rose-900 uppercase tracking-wider">Reject Timesheet</h3>
                    <p class="text-xs text-slate-500">Permanently rejects this timesheet submission.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="2" required placeholder="Provide reason for rejecting this work report..."
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white hover:bg-rose-700 transition-colors">
                    Reject Timesheet
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
