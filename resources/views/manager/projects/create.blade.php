@extends('layouts.app')

@section('title', 'Create Project')
@section('page-title', 'New Project')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Initiate New Project</h2>
            <p class="text-xs text-slate-500 mt-0.5">Define project scope, schedule, budget, client link, and assign leadership</p>
        </div>
        <a href="{{ route('manager.projects.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Back to Projects
        </a>
    </div>

    <form method="POST" action="{{ route('manager.projects.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf

        <!-- Core Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Project Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                    placeholder="e.g. NextGen Mobile Banking Platform">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Project Code <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 uppercase font-mono"
                    placeholder="e.g. PROJ-MB-2026">
                @error('code')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Assignments: Client, Team, Manager -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="client_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Client (Optional)
                </label>
                <select id="client_id" name="client_id"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">-- Internal / No Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
                @error('client_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="team_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Assigned Squad (Optional)
                </label>
                <select id="team_id" name="team_id"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    <option value="">-- No Primary Squad --</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                            {{ $team->name }} ({{ $team->code }})
                        </option>
                    @endforeach
                </select>
                @error('team_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="manager_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Project Manager <span class="text-rose-500">*</span>
                </label>
                <select id="manager_id" name="manager_id" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ old('manager_id', Auth::id()) == $manager->id ? 'selected' : '' }}>
                            {{ $manager->name }} ({{ ucfirst($manager->role->value) }})
                        </option>
                    @endforeach
                </select>
                @error('manager_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Status, Priority, Budget, Hours -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select id="status" name="status" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    @foreach(\App\Enums\ProjectStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ old('status', 'planning') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="priority" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Priority <span class="text-rose-500">*</span>
                </label>
                <select id="priority" name="priority" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                    @foreach(\App\Enums\ProjectPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" {{ old('priority', 'medium') === $priority->value ? 'selected' : '' }}>
                            {{ $priority->label() }}
                        </option>
                    @endforeach
                </select>
                @error('priority')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="budget" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Total Budget ($)
                </label>
                <input type="number" step="0.01" id="budget" name="budget" value="{{ old('budget', '0.00') }}" min="0"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('budget')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="estimated_hours" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Est. Hours
                </label>
                <input type="number" step="0.5" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours', '0.0') }}" min="0"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('estimated_hours')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="start_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Planned Start Date
                </label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('start_date')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="deadline" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Target Deadline
                </label>
                <input type="date" id="deadline" name="deadline" value="{{ old('deadline') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                @error('deadline')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description, Objectives, Scope -->
        <div class="space-y-4 pt-2 border-t border-slate-100">
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Executive Summary / Description
                </label>
                <textarea id="description" name="description" rows="3"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                    placeholder="Brief description of the project objectives and background...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="objectives" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Key Objectives
                    </label>
                    <textarea id="objectives" name="objectives" rows="3"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                        placeholder="Deliverable milestones, performance targets...">{{ old('objectives') }}</textarea>
                    @error('objectives')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="scope" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Scope & Boundaries
                    </label>
                    <textarea id="scope" name="scope" rows="3"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"
                        placeholder="In-scope vs out-of-scope criteria...">{{ old('scope') }}</textarea>
                    @error('scope')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('manager.projects.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Create Project
            </button>
        </div>
    </form>
</div>
@endsection
