@extends('layouts.app')

@section('title', 'Team Tasks')
@section('page-title', 'Squad Task Workspace')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Squad Work Items & Deliverables</h2>
            <p class="text-xs text-slate-500 mt-0.5">Tasks assigned to your squads across active projects</p>
        </div>
    </div>

    <!-- Tasks List Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Task</th>
                        <th class="px-5 py-3.5">Project</th>
                        <th class="px-5 py-3.5">Assignee</th>
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
                                    <span class="font-mono text-[10px] text-slate-400 font-semibold uppercase">{{ $task->task_code }}</span>
                                    <a href="{{ route('team-lead.tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-indigo-600 text-sm block">
                                        {{ $task->title }}
                                    </a>
                                </div>
                            </td>

                            <td class="px-5 py-4 font-semibold text-slate-800">
                                {{ $task->project->name }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $task->assignee?->name ?? 'Unassigned' }}
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
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('team-lead.tasks.show', $task) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    View Task &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                No squad tasks currently assigned.
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
