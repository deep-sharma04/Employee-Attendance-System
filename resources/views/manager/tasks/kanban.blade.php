@extends('layouts.app')

@section('title', 'Kanban Board')
@section('page-title', 'Task Board')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Interactive Kanban Board</h2>
            <p class="text-xs text-slate-500 mt-0.5">Visualize project work streams, bottlenecks, and delivery stages</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('manager.tasks.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                List View
            </a>
            <a href="{{ route('manager.tasks.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Create Task
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.tasks.kanban') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search tasks..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <div>
                <select name="project_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="assigned_to" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">All Assignees</option>
                    @foreach($assignees as $a)
                        <option value="{{ $a->id }}" {{ request('assigned_to') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'project_id', 'assigned_to', 'priority']))
                    <a href="{{ route('manager.tasks.kanban') }}" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Kanban Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 items-start">
        @php
            $statuses = [
                \App\Enums\TaskStatus::TODO,
                \App\Enums\TaskStatus::IN_PROGRESS,
                \App\Enums\TaskStatus::IN_REVIEW,
                \App\Enums\TaskStatus::BLOCKED,
                \App\Enums\TaskStatus::DONE,
            ];
        @endphp

        @foreach($statuses as $status)
            @php $columnTasks = $columns[$status->value] ?? collect(); @endphp
            <div class="bg-slate-50/70 rounded-2xl border border-slate-200 p-3 space-y-3 flex flex-col min-h-[500px]">
                <!-- Column Header -->
                <div class="flex items-center justify-between px-2 py-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">{{ $status->label() }}</span>
                        <span class="h-5 min-w-[20px] px-1.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center">
                            {{ $columnTasks->count() }}
                        </span>
                    </div>
                </div>

                <!-- Cards Container -->
                <div class="space-y-3 flex-1 overflow-y-auto max-h-[calc(100vh-280px)] pr-1">
                    @forelse($columnTasks as $task)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs hover:shadow-md transition-all p-3.5 space-y-2.5 group">
                            <!-- Card Top Meta -->
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">{{ $task->task_code }}</span>
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-full border {{ $task->priority?->badgeClass() }}">
                                    {{ $task->priority?->label() }}
                                </span>
                            </div>

                            <!-- Card Title -->
                            <a href="{{ route('manager.tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-indigo-600 text-xs block leading-snug">
                                {{ $task->title }}
                            </a>

                            <div class="text-[11px] text-slate-500 font-medium truncate">
                                {{ $task->project->name }}
                            </div>

                            <!-- Checklists Progress (if any) -->
                            @if($task->checklists->isNotEmpty())
                                @php $cProgress = $task->checklistProgress(); @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] font-semibold text-slate-400">
                                        <span>Checklist</span>
                                        <span>{{ $task->checklists->where('is_completed', true)->count() }}/{{ $task->checklists->count() }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1">
                                        <div class="bg-indigo-600 h-1 rounded-full" style="width: {{ $cProgress }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Bottom Meta: Assignee, Due Date & Quick Status -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                @if($task->assignee)
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[9px]">
                                            {{ strtoupper(substr($task->assignee->name, 0, 2)) }}
                                        </div>
                                        <span class="text-slate-700 font-medium truncate max-w-[80px]">{{ $task->assignee->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Unassigned</span>
                                @endif

                                <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                    {{ $task->due_date?->format('M d') ?? '' }}
                                </span>
                            </div>

                            <!-- Quick Status Dropdown (on hover) -->
                            <form method="POST" action="{{ route('manager.tasks.status', $task) }}" class="pt-1.5 border-t border-slate-100">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="w-full text-[10px] font-medium py-1 px-1.5 rounded-lg border border-slate-200 bg-slate-50 focus:ring-1 focus:ring-indigo-500">
                                    @foreach(\App\Enums\TaskStatus::cases() as $st)
                                        <option value="{{ $st->value }}" {{ $task->status === $st ? 'selected' : '' }}>
                                            Move: {{ $st->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @empty
                        <div class="h-24 flex items-center justify-center text-slate-300 text-xs italic border-2 border-dashed border-slate-200 rounded-xl">
                            No tasks
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
