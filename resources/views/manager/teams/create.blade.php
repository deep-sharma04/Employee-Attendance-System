@extends('layouts.app')

@section('title', 'Create Team')
@section('page-title', 'Create Operational Team')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">New Team Unit</h2>
            <p class="text-xs text-slate-500 mt-0.5">Assign exactly 1 Manager and 1 Team Lead to lead this squad</p>
        </div>
        <a href="{{ route('manager.teams.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Back to Teams
        </a>
    </div>

    <form method="POST" action="{{ route('manager.teams.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Team Name -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Team Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('name') border-rose-400 @enderror"
                    placeholder="e.g. Core Platform Engineers">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Team Code -->
            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Team Code / Identifier <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 uppercase font-mono @error('code') border-rose-400 @enderror"
                    placeholder="e.g. TEAM-CORE-01">
                @error('code')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Department -->
            <div>
                <label for="department" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Department
                </label>
                <input type="text" id="department" name="department" value="{{ old('department') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('department') border-rose-400 @enderror"
                    placeholder="e.g. Engineering, Product Design">
                @error('department')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="is_active" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Operational Status <span class="text-rose-500">*</span>
                </label>
                <select id="is_active" name="is_active" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive / Archived</option>
                </select>
            </div>

            <!-- Manager (Exactly 1) -->
            <div>
                <label for="manager_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Assigned Project Manager <span class="text-rose-500">*</span>
                </label>
                <select id="manager_id" name="manager_id" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('manager_id') border-rose-400 @enderror">
                    <option value="">Select Project Manager...</option>
                    @foreach($managers as $mgr)
                        <option value="{{ $mgr->id }}" {{ old('manager_id', Auth::id()) == $mgr->id ? 'selected' : '' }}>
                            {{ $mgr->name }} ({{ $mgr->email }})
                        </option>
                    @endforeach
                </select>
                @error('manager_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Team Lead (Exactly 1) -->
            <div>
                <label for="team_lead_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Assigned Team Lead
                </label>
                <select id="team_lead_id" name="team_lead_id"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('team_lead_id') border-rose-400 @enderror">
                    <option value="">Select Team Lead (Optional)...</option>
                    @foreach($teamLeads as $tl)
                        <option value="{{ $tl->id }}" {{ old('team_lead_id') == $tl->id ? 'selected' : '' }}>
                            {{ $tl->name }} ({{ $tl->email }})
                        </option>
                    @endforeach
                </select>
                @error('team_lead_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Team Scope & Focus
            </label>
            <textarea id="description" name="description" rows="3"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('description') border-rose-400 @enderror"
                placeholder="Domain responsibilities, stack specializations, core objectives...">{{ old('description') }}</textarea>
            @error('description')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('manager.teams.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Create Team
            </button>
        </div>
    </form>
</div>
@endsection
