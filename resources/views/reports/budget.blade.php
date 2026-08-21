@extends('layouts.app')

@section('title', 'Project Budget & Cost Utilization')
@section('page-title', 'Project Budget & Cost Utilization')

@section('content')
<div class="space-y-6">
    <!-- Header & Export -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Financial Cost & Budget Utilization</h2>
            <p class="text-xs text-slate-500 mt-1">Track approved labor costs derived from timesheets, budget consumption rates, and remaining project runway.</p>
        </div>
        <div>
            <a href="{{ route('manager.reports.export', array_merge(['type' => 'budget'], request()->query())) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.reports.budget') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Project</label>
                <select name="project_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Projects</option>
                    @foreach($projects as $proj)
                        <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>{{ $proj->name }} ({{ $proj->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Team</label>
                <select name="team_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Teams</option>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition">
                    Filter Report
                </button>
                <a href="{{ route('manager.reports.budget') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Financial KPIs -->
    @php
        $totalBudget = $budgetData->sum('budget');
        $totalLaborCost = $budgetData->sum('labor_cost');
        $totalRemaining = $budgetData->sum('budget_remaining');
        $overallConsumedPercent = $totalBudget > 0 ? round(($totalLaborCost / $totalBudget) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Allocated Budget</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">₹{{ number_format($totalBudget, 2) }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ $budgetData->count() }} Projects</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Labor Cost Consumed</p>
                <h3 class="text-2xl font-bold text-rose-700 mt-1">₹{{ number_format($totalLaborCost, 2) }}</h3>
                <span class="text-[11px] font-medium text-rose-600 font-mono">{{ $overallConsumedPercent }}% Utilized</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Remaining Budget</p>
                <h3 class="text-2xl font-bold text-emerald-700 mt-1">₹{{ number_format($totalRemaining, 2) }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Available Runway</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Logged Hours</p>
                <h3 class="text-2xl font-bold text-purple-700 mt-1">{{ number_format($budgetData->sum('logged_approved_hours'), 1) }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ number_format($budgetData->sum('estimated_hours'), 1) }} Est. Hrs</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
    </div>

    <!-- Budget Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Project Budget & Labor Cost Breakdown</h3>
                <p class="text-xs text-slate-500 mt-0.5">Calculated automatically from approved timesheet entries multiplied by employee salary cost rates.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">{{ $budgetData->count() }} Projects</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Project</th>
                        <th class="py-3.5 px-4">Total Budget</th>
                        <th class="py-3.5 px-4">Approved Labor Cost</th>
                        <th class="py-3.5 px-4">Budget Consumed %</th>
                        <th class="py-3.5 px-4">Budget Remaining</th>
                        <th class="py-3.5 px-4">Est. vs Actual Hours</th>
                        <th class="py-3.5 px-4">Utilization Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($budgetData as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900">{{ $item['project']->name }}</span>
                                <span class="block text-[11px] text-slate-500 font-mono">{{ $item['project']->code }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                ₹{{ number_format($item['budget'], 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-rose-700">
                                ₹{{ number_format($item['labor_cost'], 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full {{ $item['consumed_percent'] > 100 ? 'bg-rose-500' : ($item['consumed_percent'] >= 85 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                             style="width: {{ min(100, $item['consumed_percent']) }}%"></div>
                                    </div>
                                    <span class="font-bold font-mono text-slate-800">{{ $item['consumed_percent'] }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-emerald-700">
                                ₹{{ number_format($item['budget_remaining'], 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-700">
                                <span class="font-bold text-purple-700">{{ number_format($item['logged_approved_hours'], 1) }}h</span> / {{ number_format($item['estimated_hours'], 1) }}h
                            </td>
                            <td class="py-3.5 px-4">
                                @if($item['utilization_status'] === 'under_budget')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Under Budget
                                    </span>
                                @elseif($item['utilization_status'] === 'near_limit')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Near Limit (85%+)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Over Budget
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No project budget data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
