@extends('layouts.app')

@section('title', 'Add New Client')
@section('page-title', 'Create Client Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">New Client Account</h2>
            <p class="text-xs text-slate-500 mt-0.5">Register a new client company into the project ecosystem</p>
        </div>
        <a href="{{ route('manager.clients.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Back to Clients
        </a>
    </div>

    <form method="POST" action="{{ route('manager.clients.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Name -->
            <div>
                <label for="company_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Company Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('company_name') border-rose-400 @enderror"
                    placeholder="e.g. Acme Global Innovations">
                @error('company_name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Company Code -->
            <div>
                <label for="company_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Company Code / Acronym
                </label>
                <input type="text" id="company_code" name="company_code" value="{{ old('company_code') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 uppercase font-mono @error('company_code') border-rose-400 @enderror"
                    placeholder="e.g. ACME">
                @error('company_code')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Corporate Email <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('email') border-rose-400 @enderror"
                    placeholder="contact@acme.com">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Username <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('username') border-rose-400 @enderror"
                    placeholder="e.g. acme_admin">
                @error('username')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Password <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('password') border-rose-400 @enderror"
                    placeholder="Min. 8 characters">
                @error('password')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Phone Number
                </label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('phone') border-rose-400 @enderror"
                    placeholder="+1 (555) 000-0000">
                @error('phone')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Website -->
            <div>
                <label for="website" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Website URL
                </label>
                <input type="url" id="website" name="website" value="{{ old('website') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('website') border-rose-400 @enderror"
                    placeholder="https://acme.com">
                @error('website')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Relationship Status <span class="text-rose-500">*</span>
                </label>
                <select id="status" name="status" required
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('status') border-rose-400 @enderror">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active Account</option>
                    <option value="lead" {{ old('status') === 'lead' ? 'selected' : '' }}>Prospective Lead</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                @error('status')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Currency -->
            <div>
                <label for="currency" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Billing Currency
                </label>
                <input type="text" id="currency" name="currency" value="{{ old('currency', 'USD') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 font-mono @error('currency') border-rose-400 @enderror"
                    placeholder="USD, EUR, GBP, INR">
                @error('currency')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Billing Type -->
            <div>
                <label for="billing_type" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Billing Structure
                </label>
                <input type="text" id="billing_type" name="billing_type" value="{{ old('billing_type', 'Fixed Price') }}"
                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('billing_type') border-rose-400 @enderror"
                    placeholder="e.g. Hourly, Fixed Price, Retainer">
                @error('billing_type')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Address -->
        <div>
            <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Headquarters Address
            </label>
            <textarea id="address" name="address" rows="2"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('address') border-rose-400 @enderror"
                placeholder="Street address, City, State, Country">{{ old('address') }}</textarea>
            @error('address')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Internal Account Notes
            </label>
            <textarea id="notes" name="notes" rows="3"
                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-slate-50/50 @error('notes') border-rose-400 @enderror"
                placeholder="Key stakeholders, SLA terms, special handling requirements...">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('manager.clients.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-all">
                Create Client Profile
            </button>
        </div>
    </form>
</div>
@endsection
