@extends('layouts.app')

@section('title', 'Squad Timesheet — ' . $timesheet->employee->full_name)
@section('page-title', 'Squad Timesheet: ' . $timesheet->employee->full_name)

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
                {{ $timesheet->employee->full_name }} ({{ $timesheet->employee->employee_code }})
            </h1>
            <p class="text-xs text-slate-500">
                Period: <strong class="text-slate-800">{{ $timesheet->start_date->format('M d') }} — {{ $timesheet->end_date->format('M d, Y') }}</strong> &bull; Total: <strong class="text-indigo-600 font-bold text-sm">{{ $timesheet->total_hours }} Hours</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('team-lead.timesheets.index') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                &larr; Back to Queue
            </a>

            @if($timesheet->status->value === 'submitted')
                <form method="POST" action="{{ route('team-lead.timesheets.approve', $timesheet) }}" onsubmit="return confirm('Approve this squad member timesheet?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm shadow-emerald-600/20 transition-all">
                        Approve Timesheet
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Work Log Entries Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Itemized Work Log Entries</h3>
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

                            <td class="px-5 py-4 text-slate-600 max-w-sm">
                                {{ $entry->description ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Return Form -->
    @if($timesheet->status->value === 'submitted')
        <form method="POST" action="{{ route('team-lead.timesheets.return', $timesheet) }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4 max-w-xl">
            @csrf
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-purple-900 uppercase tracking-wider">Return for Revisions</h3>
                <p class="text-xs text-slate-500">Unlocks the timesheet for the member to adjust their logged hours.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Feedback *</label>
                <textarea name="rejection_reason" rows="2" required placeholder="Describe requested changes..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
            </div>
            <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white hover:bg-purple-700 transition-colors">
                Return to Member
            </button>
        </form>
    @endif
</div>
@endsection
