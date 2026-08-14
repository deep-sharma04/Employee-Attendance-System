@extends('layouts.app')

@section('title', 'Manage Document Types')
@section('page-title', 'Employee Document Type Classifications')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Document Type Classifications</h2>
            <p class="text-xs text-slate-500">Configure identity, education, experience, and custom document types for employee verification.</p>
        </div>
        <a href="{{ route('hr-admin.documents.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
            &larr; Back to Documents
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Document Type Form -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs h-fit">
            <h3 class="text-sm font-bold text-slate-900 mb-4">Create Document Type</h3>

            <form method="POST" action="{{ route('hr-admin.documents.types.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                        Type Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required placeholder="e.g. Passport / Degree Certificate"
                        class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_mandatory" id="is_mandatory" value="1"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_mandatory" class="text-xs font-medium text-slate-700 cursor-pointer">
                        Mark as Mandatory Verification Document
                    </label>
                </div>

                <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    Add Document Type
                </button>
            </form>
        </div>

        <!-- Document Types Listing -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden lg:col-span-2">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Configured Document Types</h3>
                <span class="text-xs font-medium text-slate-500">{{ $types->count() }} Types Defined</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Type Name</th>
                            <th class="px-4 py-3.5">Slug</th>
                            <th class="px-4 py-3.5">Mandatory</th>
                            <th class="px-4 py-3.5">Documents Attached</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($types as $type)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-4 font-bold text-slate-900">
                                    {{ $type->name }}
                                </td>
                                <td class="px-4 py-4 font-mono text-[11px] text-slate-500">
                                    {{ $type->slug }}
                                </td>
                                <td class="px-4 py-4">
                                    @if($type->is_mandatory)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Mandatory
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                            Optional
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-800">
                                    {{ $type->documents_count }} files
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick="openEditTypeModal({{ $type->id }}, '{{ addslashes($type->name) }}', {{ $type->is_mandatory ? 'true' : 'false' }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors">
                                            Edit
                                        </button>

                                        @if($type->documents_count === 0)
                                            <form method="POST" action="{{ route('hr-admin.documents.types.destroy', $type->id) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Delete this document type?')"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                                    No document types configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Document Type Modal -->
<div id="edit-type-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-4">Edit Document Type</h3>

        <form id="edit-type-form" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="edit_name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Document Type Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="edit_name" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_mandatory" id="edit_is_mandatory" value="1"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="edit_is_mandatory" class="text-xs font-medium text-slate-700 cursor-pointer">
                    Mark as Mandatory Verification Document
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" onclick="closeEditTypeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTypeModal(id, name, isMandatory) {
        const modal = document.getElementById('edit-type-modal');
        const form = document.getElementById('edit-type-form');
        const nameInput = document.getElementById('edit_name');
        const mandatoryInput = document.getElementById('edit_is_mandatory');

        form.action = `/hr-admin/documents/types/${id}`;
        nameInput.value = name;
        mandatoryInput.checked = Boolean(isMandatory);
        modal.classList.remove('hidden');
    }

    function closeEditTypeModal() {
        document.getElementById('edit-type-modal').classList.add('hidden');
    }
</script>
@endsection
