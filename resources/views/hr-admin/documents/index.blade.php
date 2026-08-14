@extends('layouts.app')

@section('title', 'Employee Document Management')
@section('page-title', 'Employee Document Repository & Verification')

@section('content')
<div class="space-y-6">
    <!-- Stat Counter Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Documents</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-slate-500">All employee files</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Verification</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-amber-600">Awaiting HR review</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Verified Documents</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['verified'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-emerald-600">Approved & archived</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rejected Documents</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['rejected'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-rose-600">Requires re-upload</span>
            </div>
            <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
    </div>

    <!-- Actions & Filter Toolbar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Search & Filters Form -->
            <form method="GET" action="{{ route('hr-admin.documents.index') }}" class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or employee..."
                    class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                <select name="employee_id" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>

                <select name="document_type_id" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Types</option>
                    @foreach($documentTypes as $type)
                        <option value="{{ $type->id }}" {{ request('document_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>

                <div class="flex items-center gap-2">
                    <select name="status" class="flex-1 rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <button type="submit" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'employee_id', 'document_type_id', 'status']))
                        <a href="{{ route('hr-admin.documents.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-xs font-medium">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('hr-admin.documents.types') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    Manage Types
                </a>
                <a href="{{ route('hr-admin.documents.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    Upload Document
                </a>
            </div>
        </div>
    </div>

    <!-- Documents Listing Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Employee</th>
                        <th class="px-4 py-3.5">Document Details</th>
                        <th class="px-4 py-3.5">File Info</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Uploaded By</th>
                        <th class="px-4 py-3.5">Verification</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $doc->employee?->first_name }} {{ $doc->employee?->last_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $doc->employee?->employee_code }} • {{ $doc->employee?->department }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $doc->title }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $doc->documentType?->name ?? 'Custom Document' }}
                                    </span>
                                    @if($doc->documentType?->is_mandatory)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                                            Mandatory
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-slate-800 font-mono text-[11px] truncate max-w-[180px]" title="{{ $doc->file_name }}">
                                    {{ $doc->file_name }}
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium">
                                    {{ number_format($doc->file_size / 1024, 1) }} KB • {{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $statusValue = $doc->status instanceof \App\Enums\DocumentStatus ? $doc->status->value : (string) $doc->status;
                                @endphp
                                @if($statusValue === 'verified')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Verified
                                    </span>
                                @elseif($statusValue === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $doc->rejection_reason }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 mr-1.5"></span> Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 mr-1.5"></span> Pending Verification
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-slate-800 font-medium">{{ $doc->uploader?->name ?? 'HR Administrator' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $doc->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @if($doc->verified_by)
                                    <div class="text-slate-800 font-medium">{{ $doc->verifier?->name ?? 'Administrator' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $doc->verified_at?->format('d M Y, H:i') }}</div>
                                @elseif($statusValue === 'rejected' && $doc->rejection_reason)
                                    <div class="text-rose-600 font-medium text-[11px] max-w-xs truncate" title="{{ $doc->rejection_reason }}">
                                        Reason: {{ $doc->rejection_reason }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Awaiting action</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- View File In Browser -->
                                    <a href="{{ route('hr-admin.documents.view', $doc->id) }}" target="_blank"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="View Document">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>

                                    <!-- Download File -->
                                    <a href="{{ route('hr-admin.documents.download', $doc->id) }}"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Download Document">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    </a>

                                    @if($statusValue !== 'verified')
                                        <!-- Verify Action -->
                                        <form method="POST" action="{{ route('hr-admin.documents.verify', $doc->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Verify Document" onclick="return confirm('Verify and approve this document?')">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if($statusValue !== 'rejected')
                                        <!-- Reject Action Modal Trigger -->
                                        <button type="button" onclick="openRejectModal({{ $doc->id }}, '{{ addslashes($doc->title) }}')"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Reject Document">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif

                                    <!-- Delete Document -->
                                    <form method="POST" action="{{ route('hr-admin.documents.destroy', $doc->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete Document" onclick="return confirm('Permanently delete this document and file?')">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 text-xs">
                                No employee documents match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Rejection Modal -->
<div id="reject-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-1">Reject Employee Document</h3>
        <p class="text-xs text-slate-500 mb-4" id="reject-doc-title">Document Title</p>

        <form id="reject-form" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label for="rejection_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Mandatory Rejection Reason <span class="text-rose-500">*</span>
                </label>
                <textarea name="rejection_reason" id="rejection_reason" rows="3" required placeholder="State why this document is invalid or rejected (e.g. illegible photocopy, wrong name, expired)..."
                    class="w-full rounded-xl border border-slate-300 p-3 text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(docId, docTitle) {
        const modal = document.getElementById('reject-modal');
        const form = document.getElementById('reject-form');
        const titleEl = document.getElementById('reject-doc-title');

        form.action = `/hr-admin/documents/${docId}/reject`;
        titleEl.textContent = `Rejecting: ${docTitle}`;
        modal.classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
    }
</script>
@endsection
