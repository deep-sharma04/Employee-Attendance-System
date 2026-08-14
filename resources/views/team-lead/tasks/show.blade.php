@extends('layouts.app')

@section('title', 'Task: ' . $task->task_code)
@section('page-title', 'Squad Task: ' . $task->task_code)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">{{ $task->task_code }}</span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $task->status?->badgeClass() }}">
                    {{ $task->status?->label() }}
                </span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $task->priority?->badgeClass() }}">
                    {{ $task->priority?->label() }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $task->title }}</h1>
            <p class="text-xs text-slate-500">Project: <strong class="text-slate-700">{{ $task->project->name }}</strong> &bull; Assignee: <strong class="text-slate-700">{{ $task->assignee?->name ?? 'Unassigned' }}</strong></p>
        </div>
        <a href="{{ route('team-lead.tasks.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Back to Squad Tasks
        </a>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Description</h3>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $task->description ?: 'No description provided.' }}</p>
            </div>

            <!-- Checklists (Read & Toggle) -->
            @if($task->checklists->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Checklists</h3>
                    <div class="space-y-2">
                        @foreach($task->checklists as $item)
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                <span class="h-4 w-4 rounded border flex items-center justify-center {{ $item->is_completed ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300' }}">
                                    @if($item->is_completed)
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    @endif
                                </span>
                                <span class="{{ $item->is_completed ? 'line-through text-slate-400' : 'text-slate-800 font-medium' }}">
                                    {{ $item->title }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Schedule & Effort</h3>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Due Date</span>
                    <span class="font-bold {{ $task->isOverdue() ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $task->due_date?->format('M d, Y') ?? 'None' }}
                    </span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Estimated Effort</span>
                    <span class="font-bold text-slate-900">{{ $task->estimated_hours }} hrs</span>
                </div>
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-500">Priority</span>
                    <span class="font-bold text-slate-900">{{ $task->priority?->label() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
