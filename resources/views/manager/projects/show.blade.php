@extends('layouts.app')

@section('title', $project->name . ' — Project Workspace')
@section('page-title', 'Project: ' . $project->name)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white font-bold text-2xl shadow-md shadow-indigo-500/20 shrink-0">
                {{ strtoupper(substr($project->name, 0, 2)) }}
            </div>
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-mono font-semibold uppercase tracking-wider text-slate-400">{{ $project->code }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->status?->badgeClass() }}">
                        {{ $project->status?->label() }}
                    </span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->priority?->badgeClass() }}">
                        {{ $project->priority?->label() }}
                    </span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $project->health?->badgeClass() }}">
                        Health: {{ $project->health?->label() }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $project->name }}</h1>
                <p class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                    @if($project->client)
                        <span>Client: <strong class="text-slate-700">{{ $project->client->company_name }}</strong></span>
                        <span>&bull;</span>
                    @endif
                    @if($project->team)
                        <span>Squad: <strong class="text-slate-700">{{ $project->team->name }}</strong></span>
                        <span>&bull;</span>
                    @endif
                    <span>Lead: <strong class="text-slate-700">{{ $project->manager?->name ?? 'Unassigned' }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end">
            <!-- Quick Status Transition Form -->
            <form method="POST" action="{{ route('manager.projects.status', $project) }}" class="flex items-center gap-2">
                @csrf
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    @foreach(\App\Enums\ProjectStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $project->status === $st ? 'selected' : '' }}>
                            Set: {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('manager.projects.documents.index', $project) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Documents
            </a>

            <a href="{{ route('manager.projects.edit', $project) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                Edit Settings
            </a>

            <form method="POST" action="{{ route('manager.projects.destroy', $project) }}" onsubmit="return confirm('Are you sure you want to delete this project?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Progress & Health Engine Metric Bar -->
    @php
        $progress = $project->progressPercentage();
        $totalMilestones = $project->milestones->count();
        $completedMilestones = $project->completedMilestones->count();
        $overdueMilestones = $project->milestones->filter(fn($m) => $m->isOverdue())->count();
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="space-y-2 md:col-span-2">
            <div class="flex justify-between items-center text-xs font-semibold">
                <span class="text-slate-500 uppercase tracking-wider">Overall Delivery Progress</span>
                <span class="text-slate-900 text-sm font-bold">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-[11px] text-slate-400">
                {{ $completedMilestones }} of {{ $totalMilestones }} milestones completed
            </p>
        </div>

        <div class="border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Timeline</span>
            <div class="text-sm font-bold text-slate-900">
                {{ $project->start_date?->format('M d, Y') ?? 'TBD' }} &rarr; {{ $project->deadline?->format('M d, Y') ?? 'TBD' }}
            </div>
            @if($project->isPastDeadline())
                <span class="inline-block text-[11px] font-bold text-rose-600">Deadline Overdue</span>
            @endif
        </div>

        <div class="border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Health Signals</span>
            <div class="flex items-center gap-2">
                @if($overdueMilestones > 0)
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                        {{ $overdueMilestones }} Overdue Milestones
                    </span>
                @else
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Milestones On Schedule
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabbed Content Section -->
    @php $activeTab = request('tab', 'overview'); @endphp
    <div class="space-y-6">
        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 flex items-center gap-6">
            <a href="{{ route('manager.projects.show', ['project' => $project, 'tab' => 'overview']) }}"
                class="pb-3 text-sm font-bold border-b-2 transition-colors {{ $activeTab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                Project Overview
            </a>
            <a href="{{ route('manager.projects.show', ['project' => $project, 'tab' => 'milestones']) }}"
                class="pb-3 text-sm font-bold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'milestones' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                Milestones & Phases
                <span class="px-2 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-700">{{ $totalMilestones }}</span>
            </a>
            <a href="{{ route('manager.projects.show', ['project' => $project, 'tab' => 'members']) }}"
                class="pb-3 text-sm font-bold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'members' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                Squad & Assigned Members
                <span class="px-2 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-700">{{ $project->projectMembers->count() }}</span>
            </a>
        </div>

        <!-- Tab 1: Project Overview -->
        @if($activeTab === 'overview')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Scope, Objectives, Description -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Project Summary</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $project->description ?: 'No executive summary provided.' }}</p>
                    </div>

                    <!-- Objectives -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Key Objectives</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $project->objectives ?: 'No specific objectives specified.' }}</p>
                    </div>

                    <!-- Scope & Boundaries -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Scope & Boundaries</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $project->scope ?: 'No scope boundaries specified.' }}</p>
                    </div>
                </div>

                <!-- Right Column: Financials & Meta Cards -->
                <div class="space-y-6">
                    <!-- Financial & Budgetary -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Budget & Capacity</h3>
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                            <span class="text-slate-500">Total Budget</span>
                            <span class="font-bold text-slate-900">₹{{ number_format($project->budget, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                            <span class="text-slate-500">Estimated Effort</span>
                            <span class="font-bold text-slate-900">{{ number_format($project->estimated_hours, 1) }} Hours</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-slate-500">Created By</span>
                            <span class="font-bold text-slate-700">{{ $project->creator?->name ?? 'System' }}</span>
                        </div>
                    </div>

                    <!-- Client Card -->
                    @if($project->client)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Client</h3>
                                <a href="{{ route('manager.clients.show', $project->client) }}" class="text-xs font-semibold text-indigo-600 hover:underline">View Client</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($project->client->company_name, 0, 2)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">{{ $project->client->company_name }}</h4>
                                    <p class="text-xs text-slate-400">{{ $project->client->email ?? 'No email on file' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Squad Card -->
                    @if($project->team)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Assigned Squad</h3>
                                <a href="{{ route('manager.teams.show', $project->team) }}" class="text-xs font-semibold text-indigo-600 hover:underline">View Squad</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($project->team->name, 0, 2)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">{{ $project->team->name }}</h4>
                                    <p class="text-xs text-slate-400">Team Lead: {{ $project->team->teamLead?->name ?? 'Unassigned' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Tab 2: Milestones & Phases (Task T221) -->
        @if($activeTab === 'milestones')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Milestones Timeline List -->
                <div class="lg:col-span-2 space-y-4">
                    @forelse($project->milestones as $milestone)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <!-- Status Toggle Button -->
                                <form method="POST" action="{{ route('manager.projects.milestones.toggle', ['project' => $project, 'milestone' => $milestone]) }}">
                                    @csrf
                                    <button type="submit" class="mt-0.5 h-6 w-6 rounded-lg border flex items-center justify-center transition-colors {{ $milestone->status === 'completed' ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 hover:border-indigo-600 text-transparent' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                </form>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-sm {{ $milestone->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                            {{ $milestone->title }}
                                        </h4>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $milestone->statusBadgeClass() }}">
                                            {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                                        </span>
                                        @if($milestone->isOverdue())
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700">Overdue</span>
                                        @endif
                                    </div>
                                    @if($milestone->description)
                                        <p class="text-xs text-slate-500 mt-1">{{ $milestone->description }}</p>
                                    @endif
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        Due: {{ $milestone->due_date?->format('M d, Y') ?? 'No deadline' }}
                                        @if($milestone->completed_at)
                                            &bull; Completed: {{ $milestone->completed_at->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('manager.projects.milestones.destroy', ['project' => $project, 'milestone' => $milestone]) }}" onsubmit="return confirm('Delete this milestone?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-400 text-xs">
                            No project milestones configured yet. Add milestones to track deliverables and calculate automated project health.
                        </div>
                    @endforelse
                </div>

                <!-- Add Milestone Form -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Add New Milestone</h3>
                    <form method="POST" action="{{ route('manager.projects.milestones.store', $project) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="m_title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Title <span class="text-rose-500">*</span></label>
                            <input type="text" id="m_title" name="title" required placeholder="e.g. Phase 1 Architecture Sign-off"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                        </div>

                        <div>
                            <label for="m_due_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Target Due Date</label>
                            <input type="date" id="m_due_date" name="due_date"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                        </div>

                        <div>
                            <label for="m_status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Initial Status</label>
                            <select id="m_status" name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div>
                            <label for="m_desc" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Description / Deliverables</label>
                            <textarea id="m_desc" name="description" rows="2" placeholder="Key delivery criteria..."
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                            Add Milestone
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Tab 3: Squad & Members -->
        @if($activeTab === 'members')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Assigned Members List -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-base">Direct Assigned Members ({{ $project->projectMembers->count() }})</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($project->projectMembers as $pm)
                            @php
                                $u = $pm->user;
                                $emp = $u?->employee;
                                $prof = $emp?->projectProfile;
                            @endphp
                            <div class="p-5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($u?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">{{ $u?->name }}</h4>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">
                                                {{ $pm->project_role?->label() ?? 'Member' }}
                                            </span>
                                            @if($prof)
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $prof->availabilityBadgeClass() }}">
                                                    {{ $prof->availabilityLabel() }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $u?->email }} &bull; Joined {{ $pm->joined_at?->format('M d, Y') ?? $pm->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($emp)
                                        <a href="{{ route('manager.employees.profiles.show', $emp) }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                                            View Profile
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('manager.projects.members.remove', ['project' => $project, 'member' => $pm]) }}" onsubmit="return confirm('Remove member from project?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-xs">
                                No direct project members assigned yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Add Member Form -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Assign User to Project</h3>
                    <form method="POST" action="{{ route('manager.projects.members.add', $project) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="add_user_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select User / Employee <span class="text-rose-500">*</span></label>
                            <select id="add_user_id" name="user_id" required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                                <option value="">-- Choose User --</option>
                                @foreach($availableUsers as $availUser)
                                    <option value="{{ $availUser->id }}">
                                        {{ $availUser->name }} ({{ $availUser->role->value }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="add_project_role" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Project Role <span class="text-rose-500">*</span></label>
                            <select id="add_project_role" name="project_role" required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50">
                                @foreach(\App\Enums\ProjectMemberRole::cases() as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                            Assign to Project
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
