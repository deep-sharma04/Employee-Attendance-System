@extends('layouts.app')

@section('title', 'Team Directory')
@section('page-title', 'Team Management')

@section('content')
<div class="space-y-6">
    <!-- Stat Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Teams</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</h3>
                <span class="text-[11px] font-medium text-slate-500">All registered units</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Teams</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['active'] }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Currently operational</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Members</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_members'] }}</h3>
                <span class="text-[11px] font-medium text-blue-600">Active roster seats</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Projects</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_projects'] }}</h3>
                <span class="text-[11px] font-medium text-purple-600">Active project links</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
        </div>
    </div>

    <!-- Controls Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('manager.teams.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <svg class="h-4 w-4 absolute left-3.5 top-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search team name, code..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <select name="status" class="py-2 px-3 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                <option value="">All Teams</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('manager.teams.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">Clear</a>
            @endif
        </form>

        <a href="{{ route('manager.teams.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Create New Team
        </a>
    </div>

    <!-- Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($teams as $team)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $team->code }}</span>
                            <h3 class="font-bold text-slate-900 text-lg hover:text-indigo-600 transition-colors">
                                <a href="{{ route('manager.teams.show', $team) }}">{{ $team->name }}</a>
                            </h3>
                            @if($team->department)
                                <span class="text-xs text-slate-500">{{ $team->department }}</span>
                            @endif
                        </div>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $team->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                            {{ $team->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Leadership Badges -->
                    <div class="mt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium">Manager</span>
                            <span class="font-semibold text-slate-900">{{ $team->manager?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-500 font-medium">Team Lead</span>
                            <span class="font-semibold text-indigo-700">{{ $team->teamLead?->name ?? 'Unassigned' }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                    <div class="flex items-center gap-3">
                        <span><strong>{{ $team->team_members_count }}</strong> Members</span>
                        <span>&bull;</span>
                        <span><strong>{{ $team->projects_count }}</strong> Projects</span>
                    </div>
                    <a href="{{ route('manager.teams.show', $team) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        Manage &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 border border-slate-200 text-center">
                <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <h3 class="text-base font-bold text-slate-900 mt-3">No teams created yet</h3>
                <p class="text-xs text-slate-500 mt-1">Organize your engineering and project personnel into operational teams.</p>
                <a href="{{ route('manager.teams.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                    Create Team
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $teams->links() }}
    </div>
</div>
@endsection
