@extends('layouts.app')

@section('title', 'My Tasks')
@section('page-title', 'My Tasks')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">My Assigned Tasks</h2>
            <p class="text-xs text-slate-500 mt-0.5">Tasks assigned to you across all projects</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('employee.tasks.recurring') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Recurring Tasks
            </a>
        </div>
    </div>

    <!-- Task KPI Summary -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">To Do</span>
            <div class="text-2xl font-bold text-slate-700 mt-1">{{ $stats['todo'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">In Progress</span>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['in_progress'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-purple-600 uppercase tracking-wider">In Review</span>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['in_review'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Done</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['done'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Overdue</span>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['overdue'] }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('employee.tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by title or task code..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <div>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\TaskStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="priority" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Priorities</option>
                    @foreach(\App\Enums\TaskPriority::cases() as $pr)
                        <option value="{{ $pr->value }}" {{ request('priority') === $pr->value ? 'selected' : '' }}>{{ $pr->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'priority', 'project_id']))
                    <a href="{{ route('employee.tasks.index') }}" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tasks List Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Task</th>
                        <th class="px-5 py-3.5">Project</th>
                        <th class="px-5 py-3.5">Priority</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Due Date</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[10px] text-slate-400 font-semibold uppercase">{{ $task->task_code }}</span>
                                        @if($task->is_recurring)
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-sm bg-indigo-50 text-indigo-700">Recurring</span>
                                        @endif
                                        @if($task->recurring_parent_id)
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-sm bg-amber-50 text-amber-700">Occurrence</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('employee.tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-indigo-600 text-sm block">
                                        {{ $task->title }}
                                    </a>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-800">{{ $task->project->name }}</span>
                                @if($task->milestone)
                                    <span class="block text-[11px] text-purple-600 font-medium">Phase: {{ $task->milestone->title }}</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $task->priority?->badgeClass() }}">
                                    {{ $task->priority?->label() }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $task->status?->badgeClass() }}">
                                    {{ $task->status?->label() }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                    {{ $task->due_date?->format('M d, Y') ?? 'No deadline' }}
                                </span>
                                @if($task->isOverdue())
                                    <span class="block text-[10px] font-bold text-rose-500">Overdue</span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('employee.tasks.show', $task) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    View &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                No tasks assigned to you matching current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
