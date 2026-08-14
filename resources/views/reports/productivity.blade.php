@extends('layouts.app')

@section('title', 'Employee Productivity Metrics')
@section('page-title', 'Employee Productivity Metrics')

@section('content')
<div class="space-y-6">
    <!-- Header & Export -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Team & Employee Productivity Metrics</h2>
            <p class="text-xs text-slate-500 mt-1">Detailed productivity tracking: on-time delivery rates, overdue counts, task velocity, and estimated vs logged hours.</p>
        </div>
        <div>
            @php
                $exportRoute = Auth::user()->isTeamLead() ? route('team-lead.reports.export', array_merge(['type' => 'productivity'], request()->query())) : route('manager.reports.export', array_merge(['type' => 'productivity'], request()->query()));
                $filterAction = Auth::user()->isTeamLead() ? route('team-lead.reports.productivity') : route('manager.reports.productivity');
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
        <form method="GET" action="{{ $filterAction }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                <select name="department" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Employee</label>
                <select name="employee_id" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition">
                    Filter Metrics
                </button>
                <a href="{{ $filterAction }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Productivity Summary Cards -->
    @php
        $totalAssigned = $productivity->sum('total_assigned');
        $totalCompleted = $productivity->sum('total_completed');
        $totalOverdue = $productivity->sum('overdue_count');
        $avgOnTime = $productivity->count() > 0 ? round($productivity->avg('on_time_percentage'), 1) : 100;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg On-Time Rate</p>
                <h3 class="text-2xl font-bold {{ $avgOnTime >= 85 ? 'text-emerald-600' : ($avgOnTime >= 70 ? 'text-amber-600' : 'text-rose-600') }} mt-1">
                    {{ $avgOnTime }}%
                </h3>
                <span class="text-[11px] font-medium text-slate-500">Across {{ $productivity->count() }} Employees</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tasks Completed</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $totalCompleted }}</h3>
                <span class="text-[11px] font-medium text-indigo-600">{{ $totalAssigned }} Total Assigned</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Overdue Tasks</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-1">{{ $totalOverdue }}</h3>
                <span class="text-[11px] font-medium text-rose-600">Requires Attention</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estimated Hours</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($productivity->sum('estimated_hours'), 1) }}</h3>
                <span class="text-[11px] font-medium text-purple-600">{{ number_format($productivity->sum('logged_approved_hours'), 1) }} Approved Logged</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
    </div>

    <!-- Productivity Detailed Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Individual Performance & On-Time Metrics</h3>
                <p class="text-xs text-slate-500 mt-0.5">Calculated based on assigned tasks, due date deadlines, and approved timesheet records.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">{{ $productivity->count() }} Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Employee</th>
                        <th class="py-3.5 px-4">Department</th>
                        <th class="py-3.5 px-4">Assigned / Done</th>
                        <th class="py-3.5 px-4">Overdue</th>
                        <th class="py-3.5 px-4">On-Time %</th>
                        <th class="py-3.5 px-4">Estimated Hrs</th>
                        <th class="py-3.5 px-4">Approved Logged Hrs</th>
                        <th class="py-3.5 px-4">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($productivity as $row)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900">{{ $row['employee']->full_name }}</span>
                                <span class="block text-[11px] text-slate-500 font-mono">{{ $row['employee']->employee_code }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700">
                                {{ $row['employee']->department ?? 'General' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-800">
                                <span class="text-emerald-700 font-bold">{{ $row['total_completed'] }}</span> / {{ $row['total_assigned'] }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($row['overdue_count'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        {{ $row['overdue_count'] }} Overdue
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">0</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full {{ $row['on_time_percentage'] >= 85 ? 'bg-emerald-500' : ($row['on_time_percentage'] >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}" 
                                             style="width: {{ $row['on_time_percentage'] }}%"></div>
                                    </div>
                                    <span class="font-bold text-slate-800 font-mono">{{ $row['on_time_percentage'] }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-700">
                                {{ number_format($row['estimated_hours'], 1) }}h
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-indigo-700">
                                {{ number_format($row['logged_approved_hours'], 1) }}h
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($row['hour_variance'] > 0)
                                    <span class="text-amber-700 font-semibold">+{{ number_format($row['hour_variance'], 1) }}h</span>
                                @elseif($row['hour_variance'] < 0)
                                    <span class="text-emerald-700 font-semibold">{{ number_format($row['hour_variance'], 1) }}h</span>
                                @else
                                    <span class="text-slate-400">0.0h</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">No employee productivity metrics available for this selection.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
