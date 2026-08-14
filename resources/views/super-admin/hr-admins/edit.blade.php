@extends('layouts.app')

@section('title', 'Edit HR Admin | Super Admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit HR Admin Account</h1>
            <p class="text-sm text-slate-500 mt-1">Update profile details, password, and active status for <strong class="text-slate-800">{{ $hrAdmin->name }}</strong> ({{ $hrAdmin->username }}).</p>
        </div>
        <a href="{{ route('super-admin.hr-admins.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1">
            &larr; Back to HR Admins
        </a>
    </div>



    <!-- Form Card -->
    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xs">
        <form method="POST" action="{{ route('super-admin.hr-admins.update', $hrAdmin->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Full Name -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $hrAdmin->name) }}" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <!-- Username (Readonly) -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Username (Immutable)</label>
                    <input type="text" value="{{ $hrAdmin->username }}" disabled class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-500 px-3.5 py-2.5 outline-hidden font-mono cursor-not-allowed">
                    <p class="text-[11px] text-slate-400 mt-1">Username cannot be changed after creation.</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Official Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $hrAdmin->email) }}" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Contact Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $hrAdmin->phone) }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                </div>

                <!-- Reset Password (Optional) -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Reset Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Optional. Minimum 8 characters.</p>
                </div>

                <!-- Active Status -->
                <div class="sm:col-span-2 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $hrAdmin->is_active)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="isActive" class="text-xs text-slate-700 font-medium">Account is Active (Uncheck to suspend access)</label>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('super-admin.hr-admins.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-6 py-2.5 rounded-xl shadow-xs transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
