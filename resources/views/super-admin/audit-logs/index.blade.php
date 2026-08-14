@extends('layouts.app')

@section('title', 'System Audit Trail | Super Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">System Audit Trail</h1>
            <p class="text-sm text-slate-500 mt-1">Immutable, cryptographically verifiable record of all administrative and operational activities.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Immutable Storage (Read-Only)
            </span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('super-admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Search Keyword</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Action, Actor, IP, Description..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Action Type</label>
                <select name="action" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" @selected(request('action') == $act)>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Actor Role</label>
                <select name="actor_role" class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected(request('actor_role') == $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
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
                <a href="{{ route('super-admin.audit-logs.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-xl transition-colors">
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
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Action</th>
                        <th class="px-6 py-3.5">Target</th>
                        <th class="px-6 py-3.5">Description</th>
                        <th class="px-6 py-3.5">IP Address</th>
                        <th class="px-6 py-3.5 text-right">Details</th>
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
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold
                                @if($log->actor_role === 'super_admin') bg-purple-100 text-purple-700
                                @elseif($log->actor_role === 'hr_admin') bg-indigo-100 text-indigo-700
                                @elseif($log->actor_role === 'employee') bg-blue-100 text-blue-700
                                @else bg-slate-100 text-slate-600 @endif">
                                {{ ucfirst(str_replace('_', ' ', $log->actor_role ?? 'system')) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 font-mono text-[11px] font-semibold text-slate-900">
                            {{ $log->action }}
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 font-mono text-[11px]">
                            {{ $log->target_type ?? 'N/A' }} @if($log->target_id)#{{ $log->target_id }}@endif
                        </td>
                        <td class="px-6 py-3.5 text-slate-600 max-w-xs truncate" title="{{ $log->description }}">
                            {{ $log->description ?? '-' }}
                        </td>
                        <td class="px-6 py-3.5 font-mono text-[11px] text-slate-500">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            @if($log->before_values || $log->after_values)
                                <button type="button" onclick="openAuditDetailModal({{ json_encode($log) }})" class="text-indigo-600 hover:text-indigo-800 font-medium text-[11px]">
                                    Inspect &rarr;
                                </button>
                            @else
                                <span class="text-slate-400 text-[11px]">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            No audit log records found matching the query.
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

<!-- Modal for Inspecting Audit Changes -->
<div id="auditDetailModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900" id="modalActionTitle">Audit Record Detail</h3>
                <p class="text-xs text-slate-500" id="modalActionMeta"></p>
            </div>
            <button type="button" onclick="closeAuditDetailModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <div class="mt-4 space-y-4 max-h-[60vh] overflow-y-auto">
            <div>
                <h4 class="text-xs font-semibold uppercase text-slate-500 mb-1">Description</h4>
                <p class="text-xs text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-200" id="modalDescription"></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-xs font-semibold uppercase text-red-600 mb-1">Before Values</h4>
                    <pre class="text-[11px] font-mono bg-slate-900 text-red-300 p-3 rounded-xl overflow-x-auto" id="modalBeforeValues">None</pre>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase text-emerald-600 mb-1">After Values</h4>
                    <pre class="text-[11px] font-mono bg-slate-900 text-emerald-300 p-3 rounded-xl overflow-x-auto" id="modalAfterValues">None</pre>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" onclick="closeAuditDetailModal()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2 rounded-xl transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openAuditDetailModal(log) {
    document.getElementById('modalActionTitle').textContent = log.action;
    document.getElementById('modalActionMeta').textContent = `Actor: ${log.actor_name || 'System'} | IP: ${log.ip_address} | ${log.created_at}`;
    document.getElementById('modalDescription').textContent = log.description || 'No description provided.';
    document.getElementById('modalBeforeValues').textContent = log.before_values ? JSON.stringify(log.before_values, null, 2) : 'None';
    document.getElementById('modalAfterValues').textContent = log.after_values ? JSON.stringify(log.after_values, null, 2) : 'None';
    document.getElementById('auditDetailModal').classList.remove('hidden');
}

function closeAuditDetailModal() {
    document.getElementById('auditDetailModal').classList.add('hidden');
}
</script>
@endsection
