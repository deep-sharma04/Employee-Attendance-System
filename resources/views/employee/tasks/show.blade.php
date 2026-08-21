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

            <!-- Task Comments (Task T230) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Internal Task Notes & Discussion</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700">Internal Only</span>
                </div>

                <div class="space-y-3">
                    @forelse($task->comments as $c)
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px]">
                                        {{ strtoupper(substr($c->user?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <span class="font-bold text-xs text-slate-900">{{ $c->user?->name }}</span>
                                    <span class="px-2 py-0.5 text-[10px] rounded-full border {{ $c->comment_type?->badgeClass() ?? 'bg-slate-100 text-slate-800' }}">{{ $c->comment_type?->label() ?? 'General' }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $c->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-700 whitespace-pre-line pl-8">{{ $c->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No internal comments posted yet.</p>
                    @endforelse
                </div>

                <!-- Add Comment Form -->
                <form method="POST" action="{{ route('employee.tasks.comments.store', $task) }}" class="space-y-2 pt-2">
                    @csrf
                    <div class="flex flex-col gap-2">
                        <select name="comment_type" class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 w-full sm:w-1/3">
                            <option value="general">General</option>
                            <option value="information_required">Information Required</option>
                            <option value="info">Info</option>
                            <!-- Note: Employees can select remark as well, based on current implementation -->
                            <option value="remark">Remark</option>
                        </select>
                        <textarea name="comment" rows="2" required placeholder="Write internal note, update, or blocker context..."
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                            Post Note
                        </button>
                    </div>
                </form>
            </div>

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
                <!-- Status Update Form -->
                <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Update Status</span>
                <form method="POST" action="{{ route('employee.tasks.status', $task) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        @foreach(\App\Enums\TaskStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ $task->status === $st ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

    </div>
</div>
@endsection
