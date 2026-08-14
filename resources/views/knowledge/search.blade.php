@extends('layouts.app')

@section('title', 'Project Knowledge Base Search')
@section('page-title', 'Project Knowledge Base')

@section('content')
<div class="space-y-6">
    <!-- Hero / Search Banner -->
    <div class="relative overflow-hidden bg-slate-900 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 max-w-3xl space-y-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Unified Knowledge Search
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Search Project Knowledge & Artifacts</h1>
            <p class="text-xs sm:text-sm text-slate-300">Quickly find project documents, specifications, task descriptions, and authorized discussions across your assigned workspace.</p>

            <!-- Search Bar Form -->
            <form method="GET" action="{{ route(request()->route()->getName()) }}" class="space-y-3 pt-2">
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $query }}" placeholder="Search documents, tasks, comments, keywords..." class="w-full text-sm text-slate-900 bg-white pl-10 pr-4 py-3 rounded-2xl shadow-lg border-0 focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-400">
                        <svg class="h-5 w-5 text-slate-400 absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>

                    <select name="project_id" class="text-xs text-slate-700 bg-white/95 border-0 rounded-2xl py-3 px-4 shadow-lg focus:ring-2 focus:ring-indigo-400">
                        <option value="">All Permitted Projects</option>
                        @foreach($availableProjects as $p)
                            <option value="{{ $p->id }}" {{ (string)$selectedProjectId === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->code }} - {{ $p->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Search Knowledge
                    </button>
                </div>

                <!-- Scope / Type Filters -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-xs text-slate-400 font-semibold mr-1">Filter Type:</span>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="all" onchange="this.form.submit()" class="sr-only" {{ $type === 'all' ? 'checked' : '' }}>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold transition-all {{ $type === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800' }}">
                            All Assets
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="documents" onchange="this.form.submit()" class="sr-only" {{ $type === 'documents' ? 'checked' : '' }}>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold transition-all {{ $type === 'documents' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800' }}">
                            Project Documents ({{ $documentResults->count() }})
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="tasks" onchange="this.form.submit()" class="sr-only" {{ $type === 'tasks' ? 'checked' : '' }}>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold transition-all {{ $type === 'tasks' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800' }}">
                            Tasks & Specs ({{ $taskResults->count() }})
                        </span>
                    </label>
                    @if($userRole !== 'client')
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="comments" onchange="this.form.submit()" class="sr-only" {{ $type === 'comments' ? 'checked' : '' }}>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold transition-all {{ $type === 'comments' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800' }}">
                                Internal Discussions ({{ $commentResults->count() }})
                            </span>
                        </label>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results Section -->
    @if(!empty($query))
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">
                Found {{ $totalResults }} {{ Str::plural('result', $totalResults) }} for <span class="text-slate-900 font-black">"{{ $query }}"</span>
            </h2>
            @if($selectedProjectId)
                <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2.5 py-1 rounded-full">
                    Filtered by Project #{{ $selectedProjectId }}
                </span>
            @endif
        </div>

        <div class="space-y-6">
            <!-- 1. Documents Section -->
            @if(($type === 'all' || $type === 'documents') && $documentResults->isNotEmpty())
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <h3 class="text-sm font-bold text-slate-900">Project Documents ({{ $documentResults->count() }})</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($documentResults as $doc)
                            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-indigo-300 transition-all space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600">
                                            {{ $doc->project?->code }} &bull; {{ $doc->project?->name }}
                                        </span>
                                        <h4 class="font-bold text-slate-900 text-sm mt-1">{{ $doc->name }}</h4>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800 shrink-0">
                                        v{{ $doc->current_version }}
                                    </span>
                                </div>

                                @if($doc->description)
                                    <p class="text-xs text-slate-600 line-clamp-2">{{ $doc->description }}</p>
                                @endif

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>Uploaded by {{ $doc->uploader?->name ?? 'System' }}</span>
                                    
                                    @php
                                        $downloadRoute = route('manager.projects.documents.download', [$doc->project, $doc]);
                                        if ($userRole === 'client') {
                                            $downloadRoute = route('client-portal.projects.documents.download', [$doc->project, $doc]);
                                        } elseif ($userRole === 'employee') {
                                            $downloadRoute = route('employee.projects.documents.download', [$doc->project, $doc]);
                                        }
                                    @endphp

                                    <a href="{{ $downloadRoute }}" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        Download v{{ $doc->current_version }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 2. Tasks Section -->
            @if(($type === 'all' || $type === 'tasks') && $taskResults->isNotEmpty())
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </span>
                        <h3 class="text-sm font-bold text-slate-900">Tasks & Specifications ({{ $taskResults->count() }})</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($taskResults as $task)
                            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-emerald-300 transition-all space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600">
                                            {{ $task->project?->code }} &bull; {{ $task->project?->name }}
                                        </span>
                                        <h4 class="font-bold text-slate-900 text-sm mt-1">{{ $task->title }}</h4>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-700 capitalize">
                                        {{ $task->status instanceof \BackedEnum ? $task->status->value : $task->status }}
                                    </span>
                                </div>

                                @if($task->description)
                                    <p class="text-xs text-slate-600 line-clamp-2">{{ $task->description }}</p>
                                @endif

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>Assignee: {{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                    <span>Due: {{ $task->due_date?->format('M d, Y') ?? 'No deadline' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 3. Internal Discussions Section (Never shown to Client) -->
            @if(($type === 'all' || $type === 'comments') && $commentResults->isNotEmpty() && $userRole !== 'client')
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                        </span>
                        <h3 class="text-sm font-bold text-slate-900">Internal Discussions & Notes ({{ $commentResults->count() }})</h3>
                    </div>

                    <div class="space-y-3">
                        @foreach($commentResults as $comment)
                            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-2">
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800">{{ $comment->user?->name ?? 'User' }}</span>
                                        <span>on task</span>
                                        <span class="font-semibold text-indigo-600">{{ $comment->task?->title }}</span>
                                        <span class="text-slate-400">({{ $comment->task?->project?->name }})</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $comment->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Empty Results -->
            @if($totalResults === 0)
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400 space-y-3">
                    <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <div class="font-bold text-slate-700 text-sm">No matching knowledge assets found</div>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">Try refining your keyword query or expanding the project filter scope.</p>
                </div>
            @endif
        </div>
    @else
        <!-- Welcome Prompt -->
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400 space-y-3">
            <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            <div class="font-bold text-slate-700 text-sm">Enter a search keyword to explore project knowledge</div>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">Search across authorized documents, specifications, tasks, checklists, and internal notes.</p>
        </div>
    @endif
</div>
@endsection
