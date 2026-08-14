@extends('layouts.app')

@section('title', 'Team Lead Dashboard')
@section('page-title', 'Team Lead Workspace Overview')

@section('content')
<div class="space-y-6">
    <!-- Top Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Team</p>
                <h3 class="text-xl font-bold text-slate-900 mt-1">{{ $team?->name ?? 'Unassigned' }}</h3>
                <span class="text-[11px] font-medium text-slate-500">{{ $team?->department ?? 'General' }}</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Team Members</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $teamMembersCount }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Active Contributors</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Team Projects</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $activeProjectsCount }}</h3>
                <span class="text-[11px] font-medium text-blue-600">Under Execution</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
        </div>
    </div>

    <!-- Team Projects Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Projects in Team Scope</h3>
                <p class="text-xs text-slate-500 mt-0.5">Projects assigned to your team or where you are an active contributor</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Project Name</th>
                        <th class="px-6 py-3.5">Client</th>
                        <th class="px-6 py-3.5">Deadline</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Health</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <div>{{ $project->name }}</div>
                                <div class="text-xs text-slate-400 font-normal font-mono">{{ $project->code }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $project->client?->company_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-mono">{{ $project->deadline?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $project->status?->badgeClass() }}">
                                    {{ $project->status?->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $project->health?->badgeClass() }}">
                                    {{ $project->health?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                No active team projects found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
