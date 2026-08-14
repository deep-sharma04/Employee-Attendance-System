@extends('layouts.app')

@section('title', $client->company_name . ' — Client Profile')
@section('page-title', 'Client Profile: ' . $client->company_name)

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white font-bold text-2xl shadow-md shadow-indigo-500/20">
                {{ strtoupper(substr($client->company_name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-semibold">{{ $client->company_code ?? 'NO-CODE' }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $client->status?->badgeClass() }}">
                        {{ $client->status?->label() }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $client->company_name }}</h1>
                <p class="text-xs text-slate-500 flex items-center gap-3 mt-1">
                    @if($client->website)
                        <a href="{{ $client->website }}" target="_blank" class="text-indigo-600 hover:underline flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            {{ parse_url($client->website, PHP_URL_HOST) ?? $client->website }}
                        </a>
                    @endif
                    @if($client->email)
                        <span>&bull;</span>
                        <span>{{ $client->email }}</span>
                    @endif
                    @if($client->phone)
                        <span>&bull;</span>
                        <span>{{ $client->phone }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="{{ route('manager.clients.edit', $client) }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Details
            </a>

            <form method="POST" action="{{ route('manager.clients.destroy', $client) }}" onsubmit="return confirm('Are you sure you want to delete this client company profile?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    @php
        $activeTab = request('tab', 'overview');
    @endphp
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'overview']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Overview
            </a>
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'contacts']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-1.5 {{ $activeTab === 'contacts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Contacts
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600 font-bold">{{ $client->contacts->count() }}</span>
            </a>
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'projects']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-1.5 {{ $activeTab === 'projects' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Linked Projects
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600 font-bold">{{ $client->projects->count() }}</span>
            </a>
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'documents']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-1.5 {{ $activeTab === 'documents' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Documents
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600 font-bold">{{ $client->documents->count() }}</span>
            </a>
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'communications']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-1.5 {{ $activeTab === 'communications' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Communication Log
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600 font-bold">{{ $client->communications->count() }}</span>
            </a>
            <a href="{{ route('manager.clients.show', ['client' => $client, 'tab' => 'portal']) }}"
               class="pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-1.5 {{ $activeTab === 'portal' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Portal Access
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600 font-bold">{{ $client->clientUsers->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- TAB 1: OVERVIEW -->
    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Company Overview</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase">Billing Currency</dt>
                            <dd class="font-mono font-bold text-slate-800 mt-0.5">{{ $client->currency ?? 'USD' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase">Billing Structure</dt>
                            <dd class="font-semibold text-slate-800 mt-0.5">{{ $client->billing_type ?? 'Standard / Not Specified' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase">Headquarters Address</dt>
                            <dd class="text-slate-700 mt-0.5">{{ $client->address ?? 'No physical address recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase">Created By</dt>
                            <dd class="text-slate-700 mt-0.5">{{ $client->creator?->name ?? 'System Admin' }} on {{ $client->created_at->format('M d, Y') }}</dd>
                        </div>
                    </dl>

                    @if($client->notes)
                        <div class="mt-6 pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Account Notes</h4>
                            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $client->notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Recent Activity Preview -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Latest Communication</h3>
                    @if($client->communications->isNotEmpty())
                        @php $latestComm = $client->communications->first(); @endphp
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase border {{ $latestComm->typeBadgeClass() }}">
                                {{ $latestComm->type }}
                            </span>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-slate-900">{{ $latestComm->subject }}</h4>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $latestComm->details }}</p>
                                <span class="text-[10px] text-slate-400 mt-2 block">{{ $latestComm->communication_date->format('M d, Y h:i A') }} by {{ $latestComm->user?->name }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-400">No communication logs recorded yet.</p>
                    @endif
                </div>
            </div>

            <!-- Quick Metrics Column -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Account Metrics</h3>
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                        <span class="text-slate-500">Active Projects</span>
                        <span class="font-bold text-slate-900">{{ $activeProjectsCount }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                        <span class="text-slate-500">Total Projects</span>
                        <span class="font-bold text-slate-900">{{ $client->projects->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                        <span class="text-slate-500">Contacts on File</span>
                        <span class="font-bold text-slate-900">{{ $client->contacts->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
                        <span class="text-slate-500">Stored Documents</span>
                        <span class="font-bold text-slate-900">{{ $client->documents->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="text-slate-500">Portal Accounts</span>
                        <span class="font-bold text-slate-900">{{ $client->clientUsers->count() }}</span>
                    </div>
                </div>

                @if($client->primaryContact)
                    <div class="bg-gradient-to-br from-indigo-50 to-white rounded-2xl border border-indigo-100 p-5">
                        <div class="flex items-center gap-2 text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Primary Contact
                        </div>
                        <h4 class="font-bold text-slate-900 text-base">{{ $client->primaryContact->name }}</h4>
                        <p class="text-xs text-slate-500">{{ $client->primaryContact->position ?? 'Point of Contact' }}</p>
                        @if($client->primaryContact->email)
                            <a href="mailto:{{ $client->primaryContact->email }}" class="text-xs text-indigo-600 hover:underline block mt-2">{{ $client->primaryContact->email }}</a>
                        @endif
                        @if($client->primaryContact->phone)
                            <span class="text-xs text-slate-600 block mt-0.5">{{ $client->primaryContact->phone }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 2: CONTACTS (T208) -->
    @if($activeTab === 'contacts')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-base">Key Client Contacts</h3>
                        <span class="text-xs text-slate-500 font-semibold">{{ $client->contacts->count() }} Registered</span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($client->contacts as $contact)
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                                <div class="flex items-start gap-3.5">
                                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($contact->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">{{ $contact->name }}</h4>
                                            @if($contact->is_primary)
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700">Primary Contact</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $contact->position ?? 'No title' }}</p>
                                        <div class="flex items-center gap-3 text-xs text-slate-400 mt-1">
                                            @if($contact->email) <span>{{ $contact->email }}</span> @endif
                                            @if($contact->phone) <span>&bull; {{ $contact->phone }}</span> @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    @if(!$contact->is_primary)
                                        <form method="POST" action="{{ route('manager.clients.contacts.primary', ['client' => $client, 'contact' => $contact]) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                                                Set Primary
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('manager.clients.contacts.destroy', ['client' => $client, 'contact' => $contact]) }}" onsubmit="return confirm('Remove contact?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-sm">
                                No contacts added yet for this client.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Add Contact Form -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="font-bold text-slate-900 text-base mb-4">Add New Contact</h3>
                <form method="POST" action="{{ route('manager.clients.contacts.store', $client) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="e.g. Alex Morgan">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Position / Designation</label>
                        <input type="text" name="position" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="e.g. Project Sponsor">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="alex@client.com">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="is_primary" name="is_primary" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_primary" class="text-xs text-slate-700 font-medium">Set as Primary Contact</label>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Notes</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="Availability hours, preferred channel..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Add Contact
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 3: PROJECTS (T209) -->
    @if($activeTab === 'projects')
        <div class="space-y-6">
            <!-- Link Project Bar -->
            @if($availableProjects->isNotEmpty())
                <div class="bg-indigo-50/60 rounded-2xl p-5 border border-indigo-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-bold text-indigo-950">Associate Existing Project</h4>
                        <p class="text-xs text-indigo-700/80">Link an unassigned project directly to {{ $client->company_name }}</p>
                    </div>
                    <form method="POST" action="{{ route('manager.clients.projects.link', $client) }}" class="flex items-center gap-2 w-full sm:w-auto">
                        @csrf
                        <select name="project_id" required class="px-3 py-2 text-xs rounded-xl border border-indigo-200 bg-white focus:outline-hidden">
                            <option value="">Select Project...</option>
                            @foreach($availableProjects as $avail)
                                <option value="{{ $avail->id }}">{{ $avail->name }} ({{ $avail->code }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors shrink-0">
                            Link Project
                        </button>
                    </form>
                </div>
            @endif

            <!-- Projects Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-base">Projects Linked to Client</h3>
                    <span class="text-xs text-slate-500 font-semibold">{{ $client->projects->count() }} Total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3.5">Project Name</th>
                                <th class="px-6 py-3.5">Code</th>
                                <th class="px-6 py-3.5">Assigned Team</th>
                                <th class="px-6 py-3.5">Deadline</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Health</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($client->projects as $proj)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $proj->name }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ $proj->code }}</td>
                                    <td class="px-6 py-4">{{ $proj->team?->name ?? '—' }}</td>
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
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('manager.clients.projects.unlink', ['client' => $client, 'project' => $proj]) }}" onsubmit="return confirm('Unlink project from client?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold">
                                                Unlink
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                        No projects currently associated with this client.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 4: DOCUMENTS (T210) -->
    @if($activeTab === 'documents')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-base">Client Documents & Files</h3>
                        <span class="text-xs text-slate-500 font-semibold">{{ $client->documents->count() }} Files</span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($client->documents as $doc)
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                                <div class="flex items-start gap-3.5">
                                    <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">{{ $doc->title }}</h4>
                                            @if($doc->is_shared_with_client)
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-cyan-100 text-cyan-800">Shared with Client</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">Internal Only</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $doc->file_name }} &bull; {{ $doc->formattedSize() }}</p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">Uploaded by {{ $doc->uploader?->name }} on {{ $doc->created_at->format('M d, Y') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <a href="{{ route('manager.clients.documents.download', ['client' => $client, 'document' => $doc]) }}"
                                       class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        Download
                                    </a>

                                    <form method="POST" action="{{ route('manager.clients.documents.toggle-share', ['client' => $client, 'document' => $doc]) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                                            {{ $doc->is_shared_with_client ? 'Make Private' : 'Share' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('manager.clients.documents.destroy', ['client' => $client, 'document' => $doc]) }}" onsubmit="return confirm('Delete this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-sm">
                                No documents stored for this client yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Upload Document Form -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="font-bold text-slate-900 text-base mb-1">Upload Document</h3>
                <p class="text-xs text-slate-400 mb-4">Max size 2MB (PDF, DOCX, XLSX, PNG, JPG)</p>
                <form method="POST" action="{{ route('manager.clients.documents.store', $client) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Document Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="e.g. Master Services Agreement 2026">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Select File <span class="text-rose-500">*</span></label>
                        <input type="file" name="document" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="is_shared" name="is_shared_with_client" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_shared" class="text-xs text-slate-700 font-medium">Visible to Client in Client Portal</label>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Notes / Description</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="Version notes, signatures pending..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Upload Document
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 5: COMMUNICATIONS (T211) -->
    @if($activeTab === 'communications')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <h3 class="font-bold text-slate-900 text-base mb-6">Interaction Timeline</h3>

                    <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        @forelse($client->communications as $comm)
                            <div class="relative">
                                <div class="absolute -left-6 top-1.5 h-3 w-3 rounded-full bg-indigo-600 border-2 border-white"></div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase border {{ $comm->typeBadgeClass() }}">
                                                {{ $comm->type }}
                                            </span>
                                            <h4 class="font-bold text-slate-900 text-sm">{{ $comm->subject }}</h4>
                                        </div>
                                        <form method="POST" action="{{ route('manager.clients.communications.destroy', ['client' => $client, 'communication' => $comm]) }}" onsubmit="return confirm('Delete this log entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-slate-400 hover:text-rose-600">&times;</button>
                                        </form>
                                    </div>
                                    <p class="text-xs text-slate-600 whitespace-pre-line">{{ $comm->details }}</p>
                                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-200/60">
                                        {{ $comm->communication_date->format('M d, Y h:i A') }} &bull; Logged by {{ $comm->user?->name ?? 'User' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-4">No communication history logged yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Log Communication Form -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="font-bold text-slate-900 text-base mb-4">Log Interaction</h3>
                <form method="POST" action="{{ route('manager.clients.communications.store', $client) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Interaction Type <span class="text-rose-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50">
                            <option value="call">Phone Call</option>
                            <option value="meeting">Video / In-person Meeting</option>
                            <option value="email">Email Thread</option>
                            <option value="note">Internal Note</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Subject / Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="subject" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="e.g. Q3 Roadmap Review">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Date & Time <span class="text-rose-500">*</span></label>
                        <input type="datetime-local" name="communication_date" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Details & Meeting Minutes <span class="text-rose-500">*</span></label>
                        <textarea name="details" rows="4" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="Action items discussed, deliverables agreed upon..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Save Interaction Log
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 6: PORTAL ACCESS (T212) -->
    @if($activeTab === 'portal')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900 text-base">Client Portal Accounts</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Authorized users with read-only portal access</p>
                        </div>
                        <span class="text-xs text-slate-500 font-semibold">{{ $client->clientUsers->count() }} Accounts</span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($client->clientUsers as $cu)
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                                <div class="flex items-start gap-3.5">
                                    <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($cu->user?->name ?? 'CU', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">{{ $cu->user?->name }}</h4>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $cu->user?->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $cu->user?->is_active ? 'Active Login' : 'Suspended' }}
                                            </span>
                                            @if($cu->is_primary)
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-700">Primary</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $cu->user?->email }} &bull; Username: <span class="font-mono">{{ $cu->user?->username }}</span></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    @if($cu->user)
                                        <form method="POST" action="{{ route('manager.clients.portal-users.toggle-status', ['client' => $client, 'user' => $cu->user]) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                                                {{ $cu->user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('manager.clients.portal-users.destroy', ['client' => $client, 'user' => $cu->user]) }}" onsubmit="return confirm('Revoke portal access for this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 transition-colors">
                                                Revoke
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-sm">
                                No client portal login accounts created yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Create Portal User Form -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="font-bold text-slate-900 text-base mb-1">Create Portal Login</h3>
                <p class="text-xs text-slate-400 mb-4">Grants read-only access to client projects</p>
                <form method="POST" action="{{ route('manager.clients.portal-users.store', $client) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">User Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="e.g. Client Lead">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Username <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 font-mono" placeholder="client_user">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="user@client.com">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Initial Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" placeholder="••••••••">
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="pu_primary" name="is_primary" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="pu_primary" class="text-xs text-slate-700 font-medium">Designate Primary Account</label>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Provision Portal Account
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
