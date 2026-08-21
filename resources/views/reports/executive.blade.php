@extends('layouts.app')

@section('title', 'Executive Project Dashboard')
@section('page-title', 'Executive Project Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header & Export Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Executive Project Analytics & Health</h2>
            <p class="text-xs text-slate-500 mt-1">High-level visibility into active portfolios, project health statuses, deadlines, and budget consumed.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('manager.reports.export', array_merge(['type' => 'executive'], request()->query())) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.reports.executive') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Team Scope</label>
                <select name="team_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Authorized Teams</option>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Client</label>
                <select name="client_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Project Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\ProjectStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Health Indicator</label>
                <select name="health" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Health Indicators</option>
                    @foreach(\App\Enums\ProjectHealth::cases() as $h)
                        <option value="{{ $h->value }}" {{ request('health') === $h->value ? 'selected' : '' }}>{{ $h->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition">
                    Apply Filter
                </button>
                <a href="{{ route('manager.reports.executive') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Executive Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Active Projects -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Projects</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $metrics['statusCounts']['active'] }}</h3>
                <span class="text-[11px] font-medium text-indigo-600">Total Portfolio: {{ $metrics['statusCounts']['total'] }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
        </div>

        <!-- Overdue & At Risk -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue Deadlines</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-1">{{ $metrics['overdueCount'] }}</h3>
                <span class="text-[11px] font-medium text-amber-600">{{ $metrics['dueSoonCount'] }} Due within 14d</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <!-- Total Budget Consumed -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Labor Cost Consumed</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">₹{{ number_format($metrics['totalLaborCost'], 2) }}</h3>
                <span class="text-[11px] font-medium text-slate-500">Budget: ₹{{ number_format($metrics['totalBudget'], 2) }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <!-- Total Approved Logged Hours -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Timesheet Hours</p>
                <h3 class="text-2xl font-bold text-purple-700 mt-1">{{ number_format($metrics['approvedHours'], 1) }} hrs</h3>
                <span class="text-[11px] font-medium text-purple-600">Utilization: {{ $metrics['budgetUtilizationPercent'] }}%</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
        </div>
    </div>

    <!-- Health Breakdown Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200">
            <span class="text-xs font-semibold text-emerald-800 uppercase">Good Health</span>
            <div class="text-xl font-bold text-emerald-900 mt-1">{{ $metrics['healthCounts']['good'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200">
            <span class="text-xs font-semibold text-amber-800 uppercase">At Risk</span>
            <div class="text-xl font-bold text-amber-900 mt-1">{{ $metrics['healthCounts']['at_risk'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200">
            <span class="text-xs font-semibold text-rose-800 uppercase">Critical</span>
            <div class="text-xl font-bold text-rose-900 mt-1">{{ $metrics['healthCounts']['critical'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-xs font-semibold text-slate-700 uppercase">Not Started</span>
            <div class="text-xl font-bold text-slate-800 mt-1">{{ $metrics['healthCounts']['not_started'] }}</div>
        </div>
    </div>

    <!-- Projects Detailed Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Project Portfolio Overview</h3>
                <p class="text-xs text-slate-500 mt-0.5">Comprehensive view of current projects, progress, budget utilization, and health indicators.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">{{ $metrics['projects']->count() }} Projects</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Project</th>
                        <th class="py-3.5 px-4">Client / Team</th>
                        <th class="py-3.5 px-4">Status & Health</th>
                        <th class="py-3.5 px-4">Budget Consumed</th>
                        <th class="py-3.5 px-4">Progress</th>
                        <th class="py-3.5 px-4">Deadline</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($metrics['projects'] as $p)
                        @php
                            $cost = app(\App\Services\Project\ProjectLaborCostService::class)->getTotalLaborCostForProject($p->id);
                            $util = $p->budget > 0 ? round(($cost / $p->budget) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900">{{ $p->name }}</span>
                                <span class="block text-[11px] text-slate-500 font-mono mt-0.5">{{ $p->code }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800">{{ $p->client?->company_name ?? 'Internal Project' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $p->team?->name ?? 'No Team' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $p->status->badgeClass() }}">
                                        {{ $p->status->label() }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $p->health->badgeClass() }}">
                                        {{ $p->health->label() }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="font-bold text-slate-900">₹{{ number_format($cost, 2) }}</div>
                                <div class="text-[11px] text-slate-500">₹{{ number_format((float) $p->budget, 2) }} ({{ $util }}%)</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $p->progressPercentage() }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700">{{ $p->progressPercentage() }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($p->deadline)
                                    <span class="{{ $p->isPastDeadline() ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                        {{ $p->deadline->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">No deadline</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('manager.projects.show', $p) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition text-xs">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No projects found for the selected criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
