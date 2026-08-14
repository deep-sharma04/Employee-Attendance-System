@extends('layouts.app')

@section('title', 'Project: ' . $project->name)
@section('page-title', 'Client Project Overview: ' . $project->code)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">{{ $project->code }}</span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->status?->badgeClass() }}">
                    {{ $project->status?->label() }}
                </span>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->health?->badgeClass() }}">
                    Health: {{ $project->health?->label() }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $project->name }}</h1>
            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                <span>Start: <strong class="text-slate-800">{{ $project->start_date?->format('M d, Y') ?? '—' }}</strong></span>
                <span>&bull;</span>
                <span>Target Deadline: <strong class="text-indigo-600 font-bold">{{ $project->deadline?->format('M d, Y') ?? 'TBD' }}</strong></span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('client-portal.dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                &larr; Back to Projects
            </a>
        </div>
    </div>

    <!-- Progress Meter -->
    @php $progress = $project->progressPercentage(); @endphp
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Overall Project Progress</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $project->milestones->where('status', 'completed')->count() }} of {{ $project->milestones->count() }} milestones completed</p>
            </div>
            <span class="text-2xl font-bold text-indigo-600">{{ $progress }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
        </div>
    </div>

    <!-- Description -->
    @if($project->description)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-2">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Project Summary & Objectives</h3>
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $project->description }}</p>
        </div>
    @endif

    <!-- Milestones & Delivery Phases (Task T244) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Milestones & Delivery Phases</h3>
            <span class="text-xs font-semibold text-slate-400">{{ $project->milestones->count() }} Total</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($project->milestones as $milestone)
                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900 text-sm">{{ $milestone->title }}</span>
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $milestone->isCompleted() ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                {{ ucfirst($milestone->status) }}
                            </span>
                        </div>
                        @if($milestone->description)
                            <p class="text-slate-500">{{ $milestone->description }}</p>
                        @endif
                    </div>

                    <div class="text-right sm:shrink-0 text-[11px] text-slate-500">
                        <span>Target: <strong class="text-slate-800">{{ $milestone->due_date?->format('M d, Y') ?? 'TBD' }}</strong></span>
                        @if($milestone->completed_at)
                            <span class="block text-emerald-600 font-semibold text-[10px]">Delivered on {{ $milestone->completed_at->format('M d, Y') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic py-4">No project milestones configured yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Deliverable Tasks / Work Items -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Project Deliverables & Work Items</h3>
            <span class="text-xs font-semibold text-slate-400">{{ $project->tasks->count() }} Items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Deliverable</th>
                        <th class="px-4 py-3">Target Date</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($project->tasks as $task)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-slate-400 uppercase text-[11px]">
                                {{ $task->task_code }}
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $task->title }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $task->due_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $task->status?->badgeClass() }}">
                                    {{ $task->status?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                No deliverables currently published for this project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
