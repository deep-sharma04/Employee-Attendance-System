@extends('layouts.app')

@section('title', 'Edit Task — ' . $task->task_code)
@section('page-title', 'Edit Task: ' . $task->task_code)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit Task: {{ $task->task_code }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Update task configuration, assignee, estimates, and dates</p>
        </div>
        <a href="{{ route('manager.tasks.show', $task) }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; View Task
        </a>
    </div>

    <form method="POST" action="{{ route('manager.tasks.update', $task) }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Title & Task Code -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <label for="title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Task Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('title')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="task_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Task Code <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="task_code" name="task_code" value="{{ old('task_code', $task->task_code) }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 uppercase font-mono">
                @error('task_code')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Milestone & Parent Task -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="milestone_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Milestone / Phase
                </label>
                <select id="milestone_id" name="milestone_id"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">-- No Milestone --</option>
                    @foreach($milestones as $milestone)
                        <option value="{{ $milestone->id }}" {{ old('milestone_id', $task->milestone_id) == $milestone->id ? 'selected' : '' }}>
                            {{ $milestone->title }}
                        </option>
                    @endforeach
                </select>
                @error('milestone_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="parent_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Parent Task (Subtask of)
                </label>
                <select id="parent_id" name="parent_id"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">-- Standalone Task --</option>
                    @foreach($parentTasks as $pt)
                        <option value="{{ $pt->id }}" {{ old('parent_id', $task->parent_id) == $pt->id ? 'selected' : '' }}>
                            {{ $pt->task_code }} - {{ $pt->title }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Assignee, Priority, Status -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="assigned_to" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Assignee
                </label>
                <select id="assigned_to" name="assigned_to"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">-- Unassigned --</option>
                    @foreach($assignees as $u)
                        @php
                            $roleLabel = $u->role instanceof \App\Enums\UserRole ? $u->role->label() : (string) $u->role;
                            $dept = $u->employee?->department ?? 'General';
                            $teams = $u->teamMemberships->pluck('team_id')->implode(',');
                        @endphp
                        <option value="{{ $u->id }}" data-teams="{{ $teams }}" {{ old('assigned_to', $task->assigned_to) == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $roleLabel }} — {{ $dept }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_to')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="priority" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Priority <span class="text-rose-500">*</span>
                </label>
                <select id="priority" name="priority" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    @foreach(\App\Enums\TaskPriority::cases() as $p)
                        <option value="{{ $p->value }}" {{ old('priority', $task->priority->value) === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
                    @endforeach
                </select>
                @error('priority')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select id="status" name="status" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    @foreach(\App\Enums\TaskStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ old('status', $task->status->value) === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Hours & Due Date -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="estimated_hours" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Estimated Hours
                </label>
                <input type="number" step="0.5" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours', $task->estimated_hours) }}" min="0"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('estimated_hours')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="actual_hours" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Actual Spent Hours
                </label>
                <input type="number" step="0.5" id="actual_hours" name="actual_hours" value="{{ old('actual_hours', $task->actual_hours) }}" min="0"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('actual_hours')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="due_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Due Date
                </label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('due_date')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Task Description & Details
            </label>
            <textarea id="description" name="description" rows="3"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">{{ old('description', $task->description) }}</textarea>
            @error('description')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Recurring Task Configuration (Task T229) -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_recurring" name="is_recurring" value="1" {{ old('is_recurring', $task->is_recurring) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_recurring" class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Enable Recurring Task (Automation)
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                <div>
                    <label for="recurrence_pattern" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Recurrence Pattern</label>
                    <select id="recurrence_pattern" name="recurrence_pattern"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-white">
                        <option value="weekly" {{ old('recurrence_pattern', $task->recurrence_pattern) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="daily" {{ old('recurrence_pattern', $task->recurrence_pattern) === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="monthly" {{ old('recurrence_pattern', $task->recurrence_pattern) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                </div>

                <div>
                    <label for="recurrence_end_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Recurrence End Date</label>
                    <input type="date" id="recurrence_end_date" name="recurrence_end_date" value="{{ old('recurrence_end_date', $task->recurrence_end_date?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-white">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('manager.tasks.show', $task) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Save Task
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const assigneeSelect = document.getElementById('assigned_to');
        if (!assigneeSelect) return;

        const allOptions = Array.from(assigneeSelect.querySelectorAll('option')).filter(opt => opt.value !== '');
        const projectTeamId = "{{ $task->project?->team_id }}";
        const currentSelectedVal = assigneeSelect.value;

        assigneeSelect.innerHTML = '<option value="">-- Unassigned --</option>';

        const teamMembers = [];
        const otherStaff = [];

        allOptions.forEach(opt => {
            const clone = opt.cloneNode(true);
            const teams = (clone.getAttribute('data-teams') || '').split(',');
            if (projectTeamId && teams.includes(projectTeamId)) {
                teamMembers.push(clone);
            } else {
                otherStaff.push(clone);
            }
        });

        if (teamMembers.length > 0) {
            const teamGroup = document.createElement('optgroup');
            teamGroup.label = '⭐ Project Team Members';
            teamMembers.forEach(opt => teamGroup.appendChild(opt));
            assigneeSelect.appendChild(teamGroup);
        }

        const staffGroup = document.createElement('optgroup');
        staffGroup.label = teamMembers.length > 0 ? 'All Other Staff Members' : 'All Staff Members';
        otherStaff.forEach(opt => staffGroup.appendChild(opt));
        assigneeSelect.appendChild(staffGroup);

        assigneeSelect.value = currentSelectedVal;
    });
</script>
@endsection
