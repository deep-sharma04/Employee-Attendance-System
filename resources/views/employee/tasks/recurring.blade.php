@extends('layouts.app')

@section('title', 'Recurring Tasks')
@section('page-title', 'Recurring Tasks')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">My Recurring Tasks</h2>
            <p class="text-xs text-slate-500 mt-0.5">Automated task definitions that generate new tasks automatically</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('employee.tasks.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to My Tasks
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('employee.tasks.recurring') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search recurring definitions by title or task code..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Search
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('employee.tasks.recurring') }}" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Recurring List Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Recurring Definition</th>
                        <th class="px-5 py-3.5">Project</th>
                        <th class="px-5 py-3.5">Pattern</th>
                        <th class="px-5 py-3.5">Generated Occurrences</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($recurringTasks as $task)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[10px] text-slate-400 font-semibold uppercase">{{ $task->task_code }}</span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-sm bg-indigo-50 text-indigo-700">Template</span>
                                    </div>
                                    <a href="{{ route('employee.tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-indigo-600 text-sm block">
                                        {{ $task->title }}
                                    </a>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-800">{{ $task->project->name }}</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700">
                                    {{ ucfirst($task->recurrence_pattern) }}
                                </span>
                                @if($task->recurrence_end_date)
                                    <span class="block text-[10px] text-slate-400 mt-1">
                                        Until {{ $task->recurrence_end_date->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="block text-[10px] text-slate-400 mt-1">Ongoing</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-bold text-slate-700 text-sm">{{ $task->recurringOccurrences->count() }}</span> occurrences
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('employee.tasks.show', $task) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    View Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                No recurring task definitions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recurringTasks->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $recurringTasks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
