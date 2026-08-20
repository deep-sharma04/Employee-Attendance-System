@extends('layouts.app')

@section('title', 'Task Details: ' . $task->task_code)
@section('page-title', 'Task Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h2 class="text-2xl font-bold text-slate-900">{{ $task->title }}</h2>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $task->status?->badgeClass() }}">
                    {{ $task->status?->label() }}
                </span>
                @if($task->is_recurring)
                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700">Recurring</span>
                @endif
                @if($task->recurring_parent_id)
                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 text-amber-700">Occurrence</span>
                @endif
            </div>
            <p class="text-sm text-slate-500 font-mono">{{ $task->task_code }} &bull; Project: <span class="font-semibold">{{ $task->project->name }}</span></p>
        </div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('employee.tasks.index') }}" class="px-4 py-2 text-sm font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
            &larr; Back
        </a>
    </div>

    <!-- Task Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Main Content -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Description</h3>
                <div class="prose prose-sm prose-slate max-w-none text-slate-600">
                    {!! nl2br(e($task->description ?? 'No description provided.')) !!}
                </div>
            </div>

            @if($task->checklists->count() > 0)
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Checklist</h3>
                    <ul class="space-y-3">
                        @foreach($task->checklists as $item)
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    @if($item->is_completed)
                                        <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    @else
                                        <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-sm font-medium {{ $item->is_completed ? 'text-slate-400 line-through' : 'text-slate-700' }}">{{ $item->title }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($task->recurring_parent_id && $task->recurringParent)
                <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 shadow-xs">
                    <h3 class="text-sm font-bold text-indigo-900 mb-2 uppercase tracking-wider">Recurring Context</h3>
                    <p class="text-sm text-indigo-700">This task was automatically generated from recurring template <a href="{{ route('employee.tasks.show', $task->recurringParent) }}" class="font-bold underline hover:text-indigo-900">{{ $task->recurringParent->task_code }}</a>.</p>
                </div>
            @endif

        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
                <div>
                    <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Assignee</span>
                    <div class="flex items-center gap-2">
                        @if($task->assignee)
                            <div class="h-7 w-7 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($task->assignee->name, 0, 2)) }}
                            </div>
                            <span class="text-sm font-bold text-slate-800">{{ $task->assignee->name }}</span>
                        @else
                            <span class="text-sm italic text-slate-400">Unassigned</span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Due Date</span>
                    @if($task->due_date)
                        <span class="text-sm font-bold {{ $task->isOverdue() ? 'text-rose-600' : 'text-slate-700' }}">
                            {{ $task->due_date->format('l, M d, Y') }}
                        </span>
                        @if($task->isOverdue())
                            <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold rounded-sm bg-rose-50 text-rose-600">OVERDUE</span>
                        @endif
                    @else
                        <span class="text-sm text-slate-500">No due date</span>
                    @endif
                </div>

                <div>
                    <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Priority</span>
                    <span class="text-sm font-bold text-slate-700">{{ $task->priority?->label() ?? 'Normal' }}</span>
                </div>

                @if($task->milestone)
                    <div>
                        <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Milestone</span>
                        <span class="text-sm font-medium text-slate-700">{{ $task->milestone->title }}</span>
                    </div>
                @endif
                
                @if($task->is_recurring)
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Recurrence Pattern</span>
                        <span class="text-sm font-medium text-slate-700">{{ ucfirst($task->recurrence_pattern) }}</span>
                        @if($task->recurrence_end_date)
                            <span class="block text-xs text-slate-500 mt-1">Until {{ $task->recurrence_end_date->format('M d, Y') }}</span>
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <p class="text-xs text-slate-500 italic">
                    Note: To update task status or add comments/hours, please use the Timesheets section or contact your Manager. Employee direct editing is restricted.
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
