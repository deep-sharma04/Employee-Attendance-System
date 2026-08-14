@extends('layouts.app')

@section('title', $task->task_code . ' — ' . $task->title)
@section('page-title', 'Task: ' . $task->task_code)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">{{ $task->task_code }}</span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $task->status?->badgeClass() }}">
                    {{ $task->status?->label() }}
                </span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $task->priority?->badgeClass() }}">
                    {{ $task->priority?->label() }}
                </span>
                @if($task->parent)
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">
                        Subtask of <a href="{{ route('manager.tasks.show', $task->parent) }}" class="underline font-mono">{{ $task->parent->task_code }}</a>
                    </span>
                @endif
                @if($task->is_recurring)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700">
                        Recurring ({{ ucfirst($task->recurrence_pattern) }})
                    </span>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-slate-900">{{ $task->title }}</h1>

            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                <span>Project: <a href="{{ route('manager.projects.show', $task->project) }}" class="font-bold text-indigo-600 hover:underline">{{ $task->project->name }}</a></span>
                @if($task->milestone)
                    <span>&bull;</span>
                    <span>Milestone: <strong class="text-slate-700">{{ $task->milestone->title }}</strong></span>
                @endif
                <span>&bull;</span>
                <span>Assignee: <strong class="text-slate-700">{{ $task->assignee?->name ?? 'Unassigned' }}</strong></span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end">
            <!-- Quick Status Transition -->
            <form method="POST" action="{{ route('manager.tasks.status', $task) }}" class="flex items-center gap-2">
                @csrf
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    @foreach(\App\Enums\TaskStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $task->status === $st ? 'selected' : '' }}>
                            Set: {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('manager.tasks.edit', $task) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                Edit Task
            </a>

            <form method="POST" action="{{ route('manager.tasks.destroy', $task) }}" onsubmit="return confirm('Are you sure you want to delete this task?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Blocker Warning Banner (Task T228) -->
    @if($isBlocked)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-rose-900">
            <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div class="space-y-1 text-xs">
                <h4 class="font-bold text-sm text-rose-800">Task is Blocked by Unfinished Dependencies</h4>
                <p class="text-rose-700">This task cannot be moved to in progress or done until the following dependencies are completed:</p>
                <ul class="list-disc list-inside space-y-0.5 pt-1 font-medium">
                    @foreach($blockers as $bTask)
                        <li>
                            <a href="{{ route('manager.tasks.show', $bTask) }}" class="underline font-bold">{{ $bTask->task_code }} - {{ $bTask->title }}</a>
                            <span class="text-[11px] text-rose-600">({{ $bTask->status?->label() }})</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (Details, Subtasks, Checklists, Dependencies, Comments, Attachments) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Description & Scope</h3>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $task->description ?: 'No detailed description provided.' }}</p>
            </div>

            <!-- Subtasks (Task T228) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Subtasks ({{ $task->subtasks->count() }})</h3>
                    <a href="{{ route('manager.tasks.create', ['project_id' => $task->project_id, 'parent_id' => $task->id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                        + Add Subtask
                    </a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($task->subtasks as $subtask)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[10px] text-slate-400 font-bold uppercase">{{ $subtask->task_code }}</span>
                                <a href="{{ route('manager.tasks.show', $subtask) }}" class="font-bold text-slate-900 hover:text-indigo-600">
                                    {{ $subtask->title }}
                                </a>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $subtask->status?->badgeClass() }}">
                                    {{ $subtask->status?->label() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No subtasks created for this task.</p>
                    @endforelse
                </div>
            </div>

            <!-- Checklists (Task T230) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                @php $cProgress = $task->checklistProgress(); @endphp
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Task Checklists</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $task->checklists->where('is_completed', true)->count() }} of {{ $task->checklists->count() }} items completed ({{ $cProgress }}%)</p>
                    </div>
                </div>

                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $cProgress }}%"></div>
                </div>

                <div class="space-y-2">
                    @forelse($task->checklists as $item)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                            <form method="POST" action="{{ route('manager.tasks.checklists.toggle', ['task' => $task, 'checklist' => $item]) }}" class="flex items-center gap-2.5">
                                @csrf
                                <input type="checkbox" onchange="this.form.submit()" {{ $item->is_completed ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="{{ $item->is_completed ? 'line-through text-slate-400' : 'text-slate-800 font-medium' }}">
                                    {{ $item->title }}
                                </span>
                            </form>
                            <form method="POST" action="{{ route('manager.tasks.checklists.destroy', ['task' => $task, 'checklist' => $item]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-300 hover:text-rose-500 p-1">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No checklist items yet.</p>
                    @endforelse
                </div>

                <!-- Add Checklist Item Form -->
                <form method="POST" action="{{ route('manager.tasks.checklists.store', $task) }}" class="flex items-center gap-2 pt-2">
                    @csrf
                    <input type="text" name="title" required placeholder="Add new checklist item..."
                        class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                        Add Item
                    </button>
                </form>
            </div>

            <!-- Dependencies & Blockers (Task T228) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Dependencies & Pre-requisites</h3>
                <div class="space-y-2">
                    @forelse($task->dependencies as $dep)
                        @php $dTask = $dep->dependsOnTask; @endphp
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-sm {{ $dep->dependency_type === 'blocks' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ strtoupper($dep->dependency_type) }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-400 font-bold uppercase">{{ $dTask?->task_code }}</span>
                                <a href="{{ route('manager.tasks.show', $dTask) }}" class="font-bold text-slate-900 hover:text-indigo-600">
                                    {{ $dTask?->title }}
                                </a>
                                <span class="px-2 py-0.5 text-[10px] rounded-full border {{ $dTask?->status?->badgeClass() }}">
                                    {{ $dTask?->status?->label() }}
                                </span>
                            </div>
                            <form method="POST" action="{{ route('manager.tasks.dependencies.destroy', ['task' => $task, 'dependency' => $dep]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-600 p-1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No dependencies configured. This task can be worked on freely.</p>
                    @endforelse
                </div>

                <!-- Add Dependency Form -->
                @if($availableDependencyTasks->isNotEmpty())
                    <form method="POST" action="{{ route('manager.tasks.dependencies.store', $task) }}" class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <select name="depends_on_task_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50">
                                <option value="">-- Choose Dependent Task --</option>
                                @foreach($availableDependencyTasks as $availTask)
                                    <option value="{{ $availTask->id }}">{{ $availTask->task_code }} - {{ $availTask->title }} ({{ $availTask->status?->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <input type="hidden" name="dependency_type" value="blocks">
                            <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                                Add Blocker
                            </button>
                        </div>
                    </form>
                @endif
            </div>

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
                                    <span class="text-[10px] text-slate-400">{{ $c->created_at->diffForHumans() }}</span>
                                </div>
                                <form method="POST" action="{{ route('manager.tasks.comments.destroy', ['task' => $task, 'comment' => $c]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-rose-500 p-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-slate-700 whitespace-pre-line pl-8">{{ $c->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No internal comments posted yet.</p>
                    @endforelse
                </div>

                <!-- Add Comment Form -->
                <form method="POST" action="{{ route('manager.tasks.comments.store', $task) }}" class="space-y-2 pt-2">
                    @csrf
                    <textarea name="comment" rows="2" required placeholder="Write internal note, update, or blocker context..."
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                            Post Note
                        </button>
                    </div>
                </form>
            </div>

            <!-- Task Attachments (Task T231) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">File Attachments</h3>
                <div class="space-y-2">
                    @forelse($task->attachments as $att)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                <span class="font-bold text-slate-800">{{ $att->file_name }}</span>
                                <span class="text-slate-400 font-mono">({{ $att->formattedSize() }})</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('manager.tasks.attachments.download', ['task' => $task, 'attachment' => $att]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                                    Download
                                </a>
                                <form method="POST" action="{{ route('manager.tasks.attachments.destroy', ['task' => $task, 'attachment' => $att]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 p-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No attachments uploaded.</p>
                    @endforelse
                </div>

                <!-- Upload Attachment Form -->
                <form method="POST" action="{{ route('manager.tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    @csrf
                    <input type="file" name="file" required class="text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                        Upload
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column (Meta Specs & Task History Timeline) -->
        <div class="space-y-6">
            <!-- Parameters Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Specifications</h3>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Status</span>
                    <span class="font-bold text-slate-900">{{ $task->status?->label() }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Priority</span>
                    <span class="font-bold text-slate-900">{{ $task->priority?->label() }}</span>
                </div>
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
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Actual Effort</span>
                    <span class="font-bold text-slate-900">{{ $task->actual_hours }} hrs</span>
                </div>
                @if($task->completed_at)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="text-slate-500">Completed At</span>
                        <span class="font-bold text-emerald-600">{{ $task->completed_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
            </div>

            <!-- Task Activity History Timeline (Task T232) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Activity History</h3>
                <div class="space-y-3 divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1">
                    @forelse($task->histories as $hist)
                        <div class="pt-3 first:pt-0 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span class="font-semibold text-slate-700">{{ $hist->user?->name ?? 'System' }}</span>
                                <span>{{ $hist->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-600">{{ $hist->details }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
