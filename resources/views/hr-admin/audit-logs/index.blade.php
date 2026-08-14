@extends('layouts.app')

@section('title', 'Operational Activity Log | HR Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Operational Activity Log</h1>
            <p class="text-sm text-slate-500 mt-1">Read-only operational history across employees, attendance, leaves, documents, and payroll cycles.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                Operational Scope
            </span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('hr-admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Action, Actor, Description..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Action Type</label>
                <select name="action" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <option value="">All Operational Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" @selected(request('action') == $act)>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors shadow-xs">
                    Apply Filters
                </button>
                <a href="{{ route('hr-admin.audit-logs.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-6 py-3.5">Timestamp</th>
                        <th class="px-6 py-3.5">Actor</th>
                        <th class="px-6 py-3.5">Action</th>
                        <th class="px-6 py-3.5">Target</th>
                        <th class="px-6 py-3.5">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-normal text-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-3.5 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-3.5 font-medium text-slate-900">
                            {{ $log->actor_name ?? 'System' }}
                        </td>
                        <td class="px-6 py-3.5 font-mono text-[11px] font-semibold text-slate-900">
                            {{ $log->action }}
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 font-mono text-[11px]">
                            {{ $log->target_type ?? 'N/A' }} @if($log->target_id)#{{ $log->target_id }}@endif
                        </td>
                        <td class="px-6 py-3.5 text-slate-600">
                            {{ $log->description ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            No operational audit log records found matching the query.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
