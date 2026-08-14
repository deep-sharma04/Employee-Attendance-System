@extends('layouts.app')

@section('title', $team->name . ' — Team Details')
@section('page-title', 'Team Profile: ' . $team->name)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white font-bold text-2xl shadow-md shadow-indigo-500/20">
                {{ strtoupper(substr($team->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $team->code }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $team->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                        {{ $team->is_active ? 'Active Squad' : 'Inactive' }}
                    </span>
                    @if($team->department)
                        <span class="text-xs text-slate-500 font-medium">&bull; {{ $team->department }}</span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $team->name }}</h1>
                @if($team->description)
                    <p class="text-xs text-slate-500 mt-1 max-w-2xl">{{ $team->description }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="{{ route('manager.teams.edit', $team) }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Team
            </a>

            <form method="POST" action="{{ route('manager.teams.destroy', $team) }}" onsubmit="return confirm('Are you sure you want to delete this team?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Leadership Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($team->manager?->name ?? 'M', 0, 2)) }}
                </div>
                <div>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Project Manager</span>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $team->manager?->name ?? 'Unassigned' }}</h4>
                    <p class="text-xs text-slate-400">{{ $team->manager?->email }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($team->teamLead?->name ?? 'TL', 0, 2)) }}
                </div>
                <div>
                    <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider">Assigned Team Lead</span>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $team->teamLead?->name ?? 'None / Unassigned' }}</h4>
                    <p class="text-xs text-slate-400">{{ $team->teamLead?->email ?? 'No team lead configured' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Members & Roster (T215) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Team Member Roster</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Assigned engineers and specialists</p>
                    </div>
                    <span class="text-xs text-slate-500 font-semibold">{{ $team->teamMembers->count() }} Seats</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($team->teamMembers as $member)
                        @php
                            $emp = $member->employee ?? $member->user?->employee;
                            $profile = $emp?->projectProfile;
                        @endphp
                        <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start gap-3.5">
                                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($emp?->first_name ?? $member->user?->name ?? 'U', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-sm">
                                            @if($emp)
                                                <a href="{{ route('manager.employees.profiles.show', $emp) }}" class="hover:text-indigo-600 transition-colors">
                                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                                </a>
                                            @else
                                                {{ $member->user?->name }}
                                            @endif
                                        </h4>
                                        @if($member->is_primary)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-700">Primary Team</span>
                                        @endif
                                        @if($profile)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $profile->availabilityBadgeClass() }}">
                                                {{ $profile->availabilityLabel() }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $emp?->email ?? $member->user?->email }} &bull; Joined {{ $member->joined_at?->format('M d, Y') ?? $member->created_at->format('M d, Y') }}</p>

                                    <!-- Skills preview -->
                                    @if($profile && !empty($profile->skills))
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach(array_slice($profile->skills, 0, 4) as $skill)
                                                <span class="px-2 py-0.5 text-[10px] rounded-md bg-slate-100 text-slate-600 font-medium">{{ $skill }}</span>
                                            @endforeach
                                            @if(count($profile->skills) > 4)
                                                <span class="px-1.5 py-0.5 text-[10px] text-slate-400 font-medium">+{{ count($profile->skills) - 4 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                @if(!$member->is_primary)
                                    <form method="POST" action="{{ route('manager.teams.members.primary', ['team' => $team, 'member' => $member]) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                                            Make Primary
                                        </button>
                                    </form>
                                @endif

                                @if($emp)
                                    <a href="{{ route('manager.employees.profiles.show', $emp) }}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                                        Skills Profile
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('manager.teams.members.remove', ['team' => $team, 'member' => $member]) }}" onsubmit="return confirm('Remove member from team?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 transition-colors">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-sm">
                            No employees assigned to this team yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Add Member Form -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <h3 class="font-bold text-slate-900 text-base mb-1">Add Team Member</h3>
            <p class="text-xs text-slate-400 mb-4">Assign existing employee to this squad</p>

            @if($availableEmployees->isNotEmpty())
                <form method="POST" action="{{ route('manager.teams.members.add', $team) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Select Employee <span class="text-rose-500">*</span></label>
                        <select name="employee_id" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50">
                            <option value="">Choose employee...</option>
                            @foreach($availableEmployees as $avail)
                                <option value="{{ $avail->id }}">
                                    {{ $avail->first_name }} {{ $avail->last_name }} ({{ $avail->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="is_primary_member" name="is_primary" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_primary_member" class="text-xs text-slate-700 font-medium">Designate as Primary Team</label>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Add to Team Roster
                    </button>
                </form>
            @else
                <p class="text-xs text-slate-500 p-4 bg-slate-50 rounded-xl">All available employees are already assigned to this team.</p>
            @endif
        </div>
    </div>

    <!-- Assigned Projects Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Projects Handled by this Team</h3>
            <span class="text-xs text-slate-500 font-semibold">{{ $team->projects->count() }} Projects</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Project Name</th>
                        <th class="px-6 py-3.5">Code</th>
                        <th class="px-6 py-3.5">Manager</th>
                        <th class="px-6 py-3.5">Deadline</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Health</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($team->projects as $proj)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $proj->name }}</td>
                            <td class="px-6 py-4 font-mono text-xs">{{ $proj->code }}</td>
                            <td class="px-6 py-4">{{ $proj->manager?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-mono">{{ $proj->deadline?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $proj->status?->badgeClass() }}">
                                    {{ $proj->status?->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $proj->health?->badgeClass() }}">
                                    {{ $proj->health?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No active projects assigned to this team yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
