@extends('layouts.app')

@section('title', 'Team Workload & Capacity View')
@section('page-title', 'Team Workload & Capacity View')

@section('content')
<div class="space-y-6">
    <!-- Header & Export -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Team Capacity & Workload Distribution</h2>
            <p class="text-xs text-slate-500 mt-1">Visualize active task load, near-term deadlines, and timesheet logged capacity across team members.</p>
        </div>
        <div>
            @php
                $exportRoute = Auth::user()->isTeamLead() ? route('team-lead.reports.export', array_merge(['type' => 'workload'], request()->query())) : route('manager.reports.export', array_merge(['type' => 'workload'], request()->query()));
                $filterAction = Auth::user()->isTeamLead() ? route('team-lead.reports.workload') : route('manager.reports.workload');
            @endphp
            <a href="{{ $exportRoute }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ $filterAction }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Select Squad / Team</label>
                <select name="team_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Authorized Teams</option>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition">
                    Filter Workload
                </button>
                <a href="{{ $filterAction }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Teams Workload Sections -->
    @forelse($workload as $teamData)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/50">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-slate-900 text-base">{{ $teamData['team']->name }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium bg-slate-200 text-slate-700">{{ $teamData['team']->code }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Manager: {{ $teamData['team']->manager?->name ?? 'N/A' }} | Lead: {{ $teamData['team']->teamLead?->name ?? 'N/A' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                        {{ $teamData['total_active_tasks'] }} Active Tasks
                    </span>
                    <span class="text-xs font-semibold text-rose-700 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-100">
                        {{ $teamData['total_due_soon'] }} Due Soon (7d)
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Team Member</th>
                            <th class="py-3.5 px-4">Active Tasks</th>
                            <th class="py-3.5 px-4">Due in 7 Days</th>
                            <th class="py-3.5 px-4">Approved Logged (Month)</th>
                            <th class="py-3.5 px-4">Pending Timesheet</th>
                            <th class="py-3.5 px-4">Capacity Utilization (160h basis)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($teamData['members'] as $mem)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900">{{ $mem['user']->name }}</span>
                                    <span class="block text-[11px] text-slate-500">{{ $mem['user']->email }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-700">
                                    {{ $mem['active_tasks_count'] }} tasks
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($mem['due_soon_count'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            {{ $mem['due_soon_count'] }} due soon
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-mono">0</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-emerald-700">
                                    {{ number_format($mem['approved_month_hours'], 1) }} hrs
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600">
                                    {{ number_format($mem['pending_month_hours'], 1) }} hrs
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-2.5 rounded-full {{ $mem['capacity_utilization'] > 100 ? 'bg-rose-500' : ($mem['capacity_utilization'] >= 75 ? 'bg-indigo-600' : 'bg-emerald-500') }}" 
                                                 style="width: {{ min(100, $mem['capacity_utilization']) }}%"></div>
                                        </div>
                                        <span class="font-bold font-mono text-slate-800">{{ $mem['capacity_utilization'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">No members assigned to this team.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500">
            No team workload data available for your authorized scope.
        </div>
    @endforelse
</div>
@endsection
