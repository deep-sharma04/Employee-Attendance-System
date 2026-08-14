@extends('layouts.app')

@section('title', 'Squad Timesheets')
@section('page-title', 'Squad Timesheet Approvals')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Squad Timesheet Reviews</h2>
            <p class="text-xs text-slate-500 mt-0.5">Review and verify work hours logged by your squad members</p>
        </div>
    </div>

    <!-- KPI Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Pending Review</span>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Approved</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['approved'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Rejected</span>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['rejected'] }}</div>
        </div>
    </div>

    <!-- Timesheet Queue Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Squad Member</th>
                        <th class="px-5 py-3.5">Period</th>
                        <th class="px-5 py-3.5">Total Hours</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Submitted Date</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($timesheets as $ts)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $ts->employee->full_name }}
                                </div>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $ts->employee->employee_code }}</span>
                            </td>

                            <td class="px-5 py-4 font-semibold text-slate-800">
                                {{ $ts->start_date->format('M d') }} — {{ $ts->end_date->format('M d, Y') }}
                            </td>

                            <td class="px-5 py-4 font-bold text-indigo-600 text-sm">
                                {{ $ts->total_hours }} hrs
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $ts->status?->badgeClass() }}">
                                    {{ $ts->status?->label() }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                {{ $ts->submitted_at?->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('team-lead.timesheets.show', $ts) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                No timesheet submissions from squad members.
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
