@extends('layouts.app')

@section('title', 'My Assigned Team')
@section('page-title', 'Team Lead Workspace')

@section('content')
<div class="space-y-6">
    @forelse($teams as $team)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <!-- Team Lead Header -->
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-md shadow-indigo-500/20">
                        {{ strtoupper(substr($team->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $team->code }}</span>
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $team->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ $team->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 mt-0.5">{{ $team->name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Manager: <strong class="text-slate-800">{{ $team->manager?->name ?? 'Unassigned' }}</strong> &bull; Department: {{ $team->department ?? 'General' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 font-semibold text-xs border border-purple-100">
                        Team Lead: {{ Auth::user()->name }}
                    </span>
                </div>
            </div>

            <!-- Team Members Roster (Read-Only) -->
            <div class="p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base">Squad Members ({{ $team->teamMembers->count() }})</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($team->teamMembers as $tm)
                        @php
                            $emp = $tm->employee ?? $tm->user?->employee;
                            $profile = $emp?->projectProfile;
                        @endphp
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($emp?->first_name ?? $tm->user?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-sm">{{ $emp?->first_name ?? $tm->user?->name }} {{ $emp?->last_name }}</h4>
                                        <span class="text-xs text-slate-400 font-mono">{{ $emp?->employee_code }}</span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $profile?->availabilityBadgeClass() ?? 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                    {{ $profile?->availabilityLabel() ?? 'Available' }}
                                </span>
                            </div>

                            <!-- Skills preview -->
                            @if($profile && !empty($profile->skills))
                                <div class="flex flex-wrap gap-1">
                                    @foreach(array_slice($profile->skills, 0, 3) as $sk)
                                        <span class="px-2 py-0.5 text-[10px] rounded-md bg-white border border-slate-200 text-slate-700 font-medium">{{ $sk }}</span>
                                    @endforeach
                                    @if(count($profile->skills) > 3)
                                        <span class="px-1.5 py-0.5 text-[10px] text-slate-400 font-medium">+{{ count($profile->skills) - 3 }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($emp)
                                <div class="pt-2 border-t border-slate-200/60 flex justify-end">
                                    <a href="{{ route('team-lead.team.members.show', $emp) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        View Skills Profile &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full p-8 text-center text-slate-400 text-xs">
                            No team members currently assigned.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Assigned Projects (Read-Only) -->
            @if($team->projects->isNotEmpty())
                <div class="px-6 pb-6 space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Assigned Projects</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($team->projects as $proj)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-sm">{{ $proj->name }}</h4>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $proj->status?->badgeClass() }}">
                                            {{ $proj->status?->label() }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1 font-mono">Code: {{ $proj->code }} &bull; Deadline: {{ $proj->deadline?->format('M d, Y') ?? 'Ongoing' }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $proj->health?->badgeClass() }}">
                                    {{ $proj->health?->label() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-400 text-sm">
            <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            <h3 class="text-base font-bold text-slate-900 mt-3">No Team Assigned</h3>
            <p class="text-xs text-slate-500 mt-1">You are currently not assigned as Team Lead for any active squad.</p>
        </div>
    @endforelse
</div>
@endsection
