@extends('layouts.app')

@section('title', 'Timesheet Approvals')
@section('page-title', 'Timesheet Approval Queue')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Timesheet Approvals</h2>
            <p class="text-xs text-slate-500 mt-0.5">Review, verify, approve, and return submitted employee project timesheets</p>
        </div>
    </div>

    <!-- KPI Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Pending Review</span>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending'] }}</div>
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

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.timesheets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\TimesheetStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="employee_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="project_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['status', 'employee_id', 'project_id']))
                    <a href="{{ route('manager.timesheets.index') }}" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Timesheet Queue Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Employee</th>
                        <th class="px-5 py-3.5">Period</th>
                        <th class="px-5 py-3.5">Total Hours</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Submitted Date</th>
                        <th class="px-5 py-3.5 text-right">Review Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($timesheets as $ts)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $ts->employee->full_name }}
                                </div>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $ts->employee->employee_code }} &bull; {{ $ts->employee->designation }}</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-800">{{ $ts->start_date->format('M d') }} — {{ $ts->end_date->format('M d, Y') }}</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-bold text-indigo-600 text-sm">{{ $ts->total_hours }} hrs</span>
                                <span class="block text-[11px] text-slate-400 font-normal">{{ $ts->entries->count() }} entries</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $ts->status?->badgeClass() }}">
                                    {{ $ts->status?->label() }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                {{ $ts->submitted_at?->format('M d, Y H:i') ?? 'Not Submitted' }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('manager.timesheets.show', $ts) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                No timesheets found matching current criteria.
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
