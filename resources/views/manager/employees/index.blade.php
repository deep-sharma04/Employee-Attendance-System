@extends('layouts.app')

@section('title', 'Project Resource Directory')
@section('page-title', 'Project Resources & Skills')

@section('content')
<div class="space-y-6">
    <!-- Stat Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Talent</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['total'] }}</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs">
                ALL
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Available</p>
                <h3 class="text-xl font-bold text-emerald-600 mt-0.5">{{ $stats['available'] }}</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                OPEN
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Partially Free</p>
                <h3 class="text-xl font-bold text-amber-600 mt-0.5">{{ $stats['partially_available'] }}</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                PART
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Allocated</p>
                <h3 class="text-xl font-bold text-blue-600 mt-0.5">{{ $stats['allocated'] }}</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                BUSY
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">On Leave</p>
                <h3 class="text-xl font-bold text-rose-600 mt-0.5">{{ $stats['on_leave'] }}</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs">
                OFF
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('manager.employees.profiles.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="h-4 w-4 absolute left-3.5 top-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, email..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <div class="w-48">
                <input type="text" name="skill" value="{{ request('skill') }}" placeholder="Filter by skill (e.g. React, PHP)" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
            </div>

            <select name="availability" class="py-2 px-3 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                <option value="">All Availability</option>
                <option value="available" {{ request('availability') === 'available' ? 'selected' : '' }}>Available</option>
                <option value="partially_available" {{ request('availability') === 'partially_available' ? 'selected' : '' }}>Partially Available</option>
                <option value="allocated" {{ request('availability') === 'allocated' ? 'selected' : '' }}>Allocated</option>
                <option value="on_leave" {{ request('availability') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'skill', 'availability']))
                <a href="{{ route('manager.employees.profiles.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">Clear</a>
            @endif
        </form>
    </div>

    <!-- Resource Directory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($employees as $emp)
            @php
                $profile = $emp->projectProfile;
                $primaryTeam = $emp->teams->firstWhere('pivot.is_primary', true);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-base">
                                {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-base hover:text-indigo-600 transition-colors">
                                    <a href="{{ route('manager.employees.profiles.show', $emp) }}">{{ $emp->first_name }} {{ $emp->last_name }}</a>
                                </h3>
                                <p class="text-xs text-slate-400 font-mono">{{ $emp->employee_code }}</p>
                            </div>
                        </div>

                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $profile?->availabilityBadgeClass() ?? 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                            {{ $profile?->availabilityLabel() ?? 'Available' }}
                        </span>
                    </div>

                    <!-- Teams & Capacity -->
                    <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Primary Squad:</span>
                            <span class="font-semibold text-slate-800">{{ $primaryTeam?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Weekly Capacity:</span>
                            <span class="font-semibold text-slate-800">{{ $profile?->weekly_capacity_hours ?? 40 }} hrs/wk</span>
                        </div>
                    </div>

                    <!-- Skills Badges -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Skills</span>
                        <div class="flex flex-wrap gap-1">
                            @if($profile && !empty($profile->skills))
                                @foreach(array_slice($profile->skills, 0, 4) as $sk)
                                    <span class="px-2 py-0.5 text-[10px] rounded-md bg-slate-100 text-slate-700 font-medium">{{ $sk }}</span>
                                @endforeach
                                @if(count($profile->skills) > 4)
                                    <span class="px-1.5 py-0.5 text-[10px] text-slate-400 font-medium">+{{ count($profile->skills) - 4 }}</span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400 italic">No skills listed</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('manager.employees.profiles.edit', $emp) }}" class="font-semibold text-slate-500 hover:text-slate-800">
                        Edit Skills
                    </a>
                    <a href="{{ route('manager.employees.profiles.show', $emp) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        View Profile &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-400 text-sm">
                No matching project resources found.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $employees->links() }}
    </div>
</div>
@endsection
