@extends('layouts.app')

@section('title', $employee->first_name . ' ' . $employee->last_name . ' — Project Resource')
@section('page-title', 'Resource Profile: ' . $employee->first_name . ' ' . $employee->last_name)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white font-bold text-2xl shadow-md shadow-indigo-500/20">
                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $employee->employee_code }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $projectProfile->availabilityBadgeClass() }}">
                        {{ $projectProfile->availabilityLabel() }}
                    </span>
                    @if($primaryTeamMembership)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">
                            {{ $primaryTeamMembership->team?->name }} (Primary)
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $employee->first_name }} {{ $employee->last_name }}</h1>
                <p class="text-xs text-slate-500 flex items-center gap-3 mt-1">
                    <span>{{ $employee->email }}</span>
                    @if($employee->phone)
                        <span>&bull;</span>
                        <span>{{ $employee->phone }}</span>
                    @endif
                    @if($projectProfile->timezone)
                        <span>&bull;</span>
                        <span>{{ $projectProfile->timezone }}</span>
                    @endif
                </p>
            </div>
        </div>

        @if(Auth::user()->isManager() || Auth::user()->isSuperAdmin())
            <a href="{{ route('manager.employees.profiles.edit', $employee) }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Skills & Capacity
            </a>
        @endif
    </div>

    <!-- Main Profile Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <!-- Skills & Specializations -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Technical Skills & Expertise</h3>
                @if(!empty($projectProfile->skills))
                    <div class="flex flex-wrap gap-2">
                        @foreach($projectProfile->skills as $skill)
                            <span class="px-3 py-1 text-xs font-semibold rounded-xl bg-slate-100 text-slate-800 border border-slate-200">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No skills listed yet for this project member.</p>
                @endif

                @if($projectProfile->bio)
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Project Bio / Experience Summary</h4>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $projectProfile->bio }}</p>
                    </div>
                @endif
            </div>

            <!-- Team Memberships -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-base">Squad & Team Assignments</h3>
                    <span class="text-xs text-slate-500 font-semibold">{{ $employee->teamMemberships->count() }} Teams</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($employee->teamMemberships as $tm)
                        <div class="p-5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($tm->team?->name ?? 'T', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-sm">{{ $tm->team?->name }}</h4>
                                        @if($tm->is_primary)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700">Primary Team</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400">Joined {{ $tm->joined_at?->format('M d, Y') ?? $tm->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>

                            @if(Auth::user()->isManager() || Auth::user()->isSuperAdmin())
                                <a href="{{ route('manager.teams.show', $tm->team) }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                                    View Team &rarr;
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">
                            Not currently assigned to any squads or teams.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Active Project Assignments -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-base">Assigned Projects</h3>
                    <span class="text-xs text-slate-500 font-semibold">{{ $employee->user?->projectMemberships->count() ?? 0 }} Projects</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($employee->user?->projectMemberships ?? [] as $pm)
                        <div class="p-5 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900 text-sm">{{ $pm->project?->name }}</h4>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $pm->project?->status?->badgeClass() }}">
                                        {{ $pm->project?->status?->label() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">Project Role: <span class="font-semibold text-slate-700">{{ $pm->role?->label() ?? 'Member' }}</span></p>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">
                            No direct project assignments recorded.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Resource Stats Column -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Capacity & Availability</h3>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Availability</span>
                    <span class="font-bold text-slate-900">{{ $projectProfile->availabilityLabel() }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Weekly Capacity</span>
                    <span class="font-bold text-slate-900">{{ $projectProfile->weekly_capacity_hours }} hrs/week</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                    <span class="text-slate-500">Industry Experience</span>
                    <span class="font-bold text-slate-900">{{ $projectProfile->experience_years ? $projectProfile->experience_years . ' Years' : 'Not specified' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-500">Primary Squad</span>
                    <span class="font-bold text-indigo-700">{{ $primaryTeamMembership->team?->name ?? 'Unassigned' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
