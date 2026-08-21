@extends('layouts.app')

@section('title', 'Timesheet — ' . $timesheet->start_date->format('M d') . ' to ' . $timesheet->end_date->format('M d, Y'))
@section('page-title', 'Project Timesheet Details')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $timesheet->status?->badgeClass() }}">
                    {{ $timesheet->status?->label() }}
                </span>
                <span class="text-xs text-slate-400 font-mono uppercase">{{ $timesheet->period_type }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $timesheet->start_date->format('M d') }} — {{ $timesheet->end_date->format('M d, Y') }}
            </h1>
            <p class="text-xs text-slate-500">
                Logged Effort: <strong class="text-indigo-600 text-sm font-bold">{{ $timesheet->total_hours }} Hours</strong> across {{ $timesheet->entries->count() }} entries
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
            <a href="{{ route('employee.timesheets.index') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                &larr; Back to List
            </a>

            @if($timesheet->isEditable())
                <form method="POST" action="{{ route('employee.timesheets.submit', $timesheet) }}" onsubmit="return confirm('Submit this timesheet for management approval? You will not be able to edit entries while under review.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm shadow-emerald-600/20 transition-all">
                        Submit Timesheet &rarr;
                    </button>
                </form>

                <form method="POST" action="{{ route('employee.timesheets.destroy', $timesheet) }}" onsubmit="return confirm('Are you sure you want to delete this draft timesheet?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                        Delete Draft
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Status Alert Banners -->
    @if($timesheet->status->value === 'returned')
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-start gap-3 text-purple-900">
            <svg class="h-5 w-5 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div class="space-y-1 text-xs">
                <h4 class="font-bold text-sm text-purple-800">Timesheet Returned for Revisions</h4>
                <p class="text-purple-700"><strong>Reviewer Remark:</strong> {{ $timesheet->rejection_reason }}</p>
                <p class="text-purple-600">Please make necessary adjustments to your logged entries below and re-submit for approval.</p>
            </div>
        </div>
    @elseif($timesheet->status->value === 'rejected')
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-rose-900">
            <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="space-y-1 text-xs">
                <h4 class="font-bold text-sm text-rose-800">Timesheet Rejected</h4>
                <p class="text-rose-700"><strong>Reason:</strong> {{ $timesheet->rejection_reason }}</p>
            </div>
        </div>
    @elseif($timesheet->status->value === 'submitted')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-amber-900">
            <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="space-y-0.5 text-xs">
                <h4 class="font-bold text-sm text-amber-800">Submitted & Pending Management Review</h4>
                <p class="text-amber-700">Your timesheet has been submitted. Editing is locked while review is in progress.</p>
            </div>
        </div>
    @elseif($timesheet->status->value === 'approved')
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-start gap-3 text-emerald-900">
            <svg class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="space-y-0.5 text-xs">
                <h4 class="font-bold text-sm text-emerald-800">Approved by {{ $timesheet->approver?->name }} on {{ $timesheet->approved_at?->format('M d, Y') }}</h4>
                <p class="text-emerald-700">All work logs have been approved and reconciled against project effort budgets.</p>
            </div>
        </div>
    @endif

    <!-- Work Log Entries Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Logged Work Entries</h3>
            <span class="text-xs font-semibold text-slate-500">Total: {{ $timesheet->total_hours }} hrs</span>
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
                        <th class="px-5 py-3.5">Notes</th>
                        @if($timesheet->isEditable())
                            <th class="px-5 py-3.5 text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($timesheet->entries as $entry)
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

                            <td class="px-5 py-4 text-slate-600 max-w-xs">
                                {{ $entry->description ?: '—' }}
                            </td>

                            @if($timesheet->isEditable())
                                <td class="px-5 py-4 text-right">
                                    <form method="POST" action="{{ route('employee.timesheets.entries.destroy', ['timesheet' => $timesheet, 'entry' => $entry]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 font-semibold text-xs">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $timesheet->isEditable() ? 7 : 6 }}" class="px-5 py-12 text-center text-slate-400">
                                No work entries logged yet. Use the form below to record daily hours.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Work Log Entry Form (Task T237) -->
    @if($timesheet->isEditable())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">+ Log Work Effort</h3>

            <form method="POST" action="{{ route('employee.timesheets.entries.store', $timesheet) }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Date *</label>
                    <input type="date" name="entry_date" required min="{{ $timesheet->start_date->toDateString() }}" max="{{ $timesheet->end_date->toDateString() }}" value="{{ old('entry_date', $timesheet->start_date->toDateString()) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Project *</label>
                    <select name="project_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Task (Optional)</label>
                    <select name="task_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                        <option value="">-- General Project Task --</option>
                        @foreach($tasks as $t)
                            <option value="{{ $t->id }}">{{ $t->task_code }} - {{ $t->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Hours *</label>
                    <input type="number" step="0.25" min="0.25" max="24" name="hours" required value="{{ old('hours', '8.0') }}"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Work Description / Deliverables</label>
                    <input type="text" name="description" placeholder="e.g. Fixed Stripe webhook payload parsing bug..."
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                </div>

                <div class="md:col-span-6 flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" name="is_billable" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-medium">Mark as Billable Effort</span>
                    </label>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                        Add Work Entry
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
