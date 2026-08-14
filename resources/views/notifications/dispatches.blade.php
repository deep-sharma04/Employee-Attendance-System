@extends('layouts.app')

@section('title', 'Notification Dispatch Logs | HRM')
@section('page-title', 'Notification Dispatch Logs')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notification Dispatch Audit Logs</h1>
            <p class="text-xs text-slate-500 mt-1">Audit trail of notification attempts, delivery channels, recipients, and dispatch statuses.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                &larr; Notification Center
            </a>
            <a href="{{ route('notifications.preferences') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                Preferences
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('notifications.dispatches') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Channel</label>
                <select name="channel" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Channels</option>
                    <option value="in_app" {{ request('channel') === 'in_app' ? 'selected' : '' }}>In-App</option>
                    <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="web_push" {{ request('channel') === 'web_push' ? 'selected' : '' }}>Web-Push</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent / Delivered</option>
                    <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Skipped (Opt-out)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Category</label>
                <select name="category" class="w-full text-xs rounded-xl border-slate-200 text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Categories</option>
                    <option value="task_assignment" {{ request('category') === 'task_assignment' ? 'selected' : '' }}>Task Assignment</option>
                    <option value="deadlines" {{ request('category') === 'deadlines' ? 'selected' : '' }}>Deadlines</option>
                    <option value="timesheets" {{ request('category') === 'timesheets' ? 'selected' : '' }}>Timesheets</option>
                    <option value="project_milestones" {{ request('category') === 'project_milestones' ? 'selected' : '' }}>Milestones</option>
                    <option value="daily_summary" {{ request('category') === 'daily_summary' ? 'selected' : '' }}>Daily Summary</option>
                    <option value="security" {{ request('category') === 'security' ? 'selected' : '' }}>Security</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition">
                    Filter Logs
                </button>
                <a href="{{ route('notifications.dispatches') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Dispatches Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Dispatch History</h3>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">{{ $dispatches->total() }} Total Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Recipient</th>
                        <th class="py-3.5 px-4">Notification Type</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Channel</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($dispatches as $dispatch)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900">{{ $dispatch->user?->name ?? 'N/A' }}</span>
                                <span class="block text-[11px] text-slate-500">{{ $dispatch->recipient_email }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-800">
                                {{ $dispatch->notification_type }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                                    {{ strtoupper(str_replace('_', ' ', $dispatch->category)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                @if($dispatch->channel === 'email')
                                    <span class="inline-flex items-center gap-1 text-indigo-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        Email
                                    </span>
                                @elseif($dispatch->channel === 'in_app')
                                    <span class="inline-flex items-center gap-1 text-emerald-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                        In-App
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-purple-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        Web-Push
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($dispatch->status === 'sent')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Sent
                                    </span>
                                @elseif($dispatch->status === 'skipped')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Skipped (Opt-out)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800" title="{{ $dispatch->error_message }}">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500">
                                {{ $dispatch->created_at ? $dispatch->created_at->format('M d, Y H:i:s') : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No notification dispatch logs recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dispatches->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $dispatches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
