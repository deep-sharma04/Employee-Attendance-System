@extends('layouts.app')

@section('title', 'Edit Project Profile — ' . $employee->first_name)
@section('page-title', 'Edit Resource Profile: ' . $employee->first_name . ' ' . $employee->last_name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Project Capability Profile</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage skills, availability, and weekly hours without modifying core HR records</p>
        </div>
        <a href="{{ route('manager.employees.profiles.show', $employee) }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; View Profile
        </a>
    </div>

    <form method="POST" action="{{ route('manager.employees.profiles.update', $employee) }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Skills Input -->
        <div>
            <label for="skills" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Technical Skills & Frameworks
            </label>
            <input type="text" id="skills" name="skills" value="{{ old('skills', is_array($projectProfile->skills) ? implode(', ', $projectProfile->skills) : '') }}"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                placeholder="e.g. PHP, Laravel, React, TypeScript, Docker, AWS">
            <p class="text-[11px] text-slate-400 mt-1">Separate skills with commas (e.g. "PHP, Laravel, Vue.js")</p>
            @error('skills')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Availability Status -->
            <div>
                <label for="availability_status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Availability Status <span class="text-rose-500">*</span>
                </label>
                <select id="availability_status" name="availability_status" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="available" {{ old('availability_status', $projectProfile->availability_status) === 'available' ? 'selected' : '' }}>Fully Available</option>
                    <option value="partially_available" {{ old('availability_status', $projectProfile->availability_status) === 'partially_available' ? 'selected' : '' }}>Partially Available</option>
                    <option value="allocated" {{ old('availability_status', $projectProfile->availability_status) === 'allocated' ? 'selected' : '' }}>Fully Allocated</option>
                    <option value="on_leave" {{ old('availability_status', $projectProfile->availability_status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                </select>
                @error('availability_status')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weekly Capacity -->
            <div>
                <label for="weekly_capacity_hours" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Weekly Capacity (Hours) <span class="text-rose-500">*</span>
                </label>
                <input type="number" id="weekly_capacity_hours" name="weekly_capacity_hours" value="{{ old('weekly_capacity_hours', $projectProfile->weekly_capacity_hours ?? 40) }}" required min="1" max="168"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('weekly_capacity_hours')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Experience Years -->
            <div>
                <label for="experience_years" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Years of Relevant Experience
                </label>
                <input type="number" step="0.5" id="experience_years" name="experience_years" value="{{ old('experience_years', $projectProfile->experience_years) }}" min="0" max="60"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                    placeholder="e.g. 5.5">
                @error('experience_years')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Timezone -->
            <div>
                <label for="timezone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Working Timezone
                </label>
                <input type="text" id="timezone" name="timezone" value="{{ old('timezone', $projectProfile->timezone ?? 'UTC') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                    placeholder="e.g. UTC, America/New_York, Asia/Kolkata">
                @error('timezone')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Bio -->
        <div>
            <label for="bio" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Project Bio & Highlights
            </label>
            <textarea id="bio" name="bio" rows="4"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                placeholder="Key accomplishments, core domains, architecture focus...">{{ old('bio', $projectProfile->bio) }}</textarea>
            @error('bio')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('manager.employees.profiles.show', $employee) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Save Project Profile
            </button>
        </div>
    </form>
</div>
@endsection
