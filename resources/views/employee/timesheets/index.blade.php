@extends('layouts.app')

@section('title', 'My Timesheets')
@section('page-title', 'My Project Timesheets')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Timesheets & Work Logs</h2>
            <p class="text-xs text-slate-500 mt-0.5">Log project effort, submit weekly work reports, and track approval status</p>
        </div>
        <a href="{{ route('employee.timesheets.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            New Weekly Timesheet
        </a>
    </div>

    <!-- Timesheets Summary KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Draft</span>
            <div class="text-2xl font-bold text-slate-700 mt-1">{{ $stats['draft'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Submitted</span>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['submitted'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Approved</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['approved'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Returned</span>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['returned'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Rejected</span>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['rejected'] }}</div>
        </div>
    </div>

    <!-- Timesheets List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Period</th>
                        <th class="px-5 py-3.5">Total Hours</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Submitted At</th>
                        <th class="px-5 py-3.5">Reviewed By</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($timesheets as $ts)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $ts->start_date->format('M d') }} — {{ $ts->end_date->format('M d, Y') }}
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal uppercase">{{ $ts->period_type }} timesheet</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="text-sm font-bold text-indigo-600">{{ $ts->total_hours }} hrs</span>
                                <span class="block text-[11px] text-slate-400 font-normal">{{ $ts->entries->count() }} work logs</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $ts->status?->badgeClass() }}">
                                    {{ $ts->status?->label() }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                {{ $ts->submitted_at?->format('M d, Y H:i') ?? '—' }}
                            </td>

                            <td class="px-5 py-4">
                                @if($ts->approver)
                                    <span class="text-slate-800 font-semibold">{{ $ts->approver->name }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $ts->approved_at?->format('M d, Y') }}</span>
                                @else
                                    <span class="text-slate-400 italic">Pending</span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('employee.timesheets.show', $ts) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    View / Log Hours &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                No timesheets created yet. Click "New Weekly Timesheet" to start logging project hours.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($timesheets->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $timesheets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
