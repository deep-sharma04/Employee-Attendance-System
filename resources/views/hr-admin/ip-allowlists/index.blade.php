@extends('layouts.app')

@section('title', 'Office IP Allowlist')
@section('page-title', 'Office IP Network Security')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Approved IP Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-xs space-y-4">
        <div class="border-b border-slate-100 dark:border-slate-700/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Approved Office IP</h3>
            <p class="text-xs text-slate-400">Attendance punches are strictly accepted only from these approved networks.</p>
        </div>

        <form method="POST" action="{{ route('hr-admin.ip-allowlists.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="ip_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    IP Address (IPv4 / IPv6) <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="ip_address" name="ip_address" required value="{{ old('ip_address') }}"
                    placeholder="e.g. 192.168.1.100 or 10.0.0.1"
                    class="mt-1.5 block w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('ip_address') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Network Description
                </label>
                <input type="text" id="description" name="description" value="{{ old('description') }}"
                    placeholder="e.g. Headquarters Main Gateway / Floor 2 Wi-Fi"
                    class="mt-1.5 block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('description') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 font-semibold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Enable IP network immediately</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all">
                    Authorize Office IP
                </button>
            </div>
        </form>

        <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700/40 text-[11px] text-slate-500 dark:text-slate-400 space-y-1">
            <p class="font-bold text-slate-700 dark:text-slate-300">💡 Current Client IP: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ request()->ip() }}</span></p>
            <p>Ensure your company gateway IP is allowlisted so employees at the physical office can punch attendance successfully.</p>
        </div>
    </div>

    <!-- Allowlisted IPs Table -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Authorized Office Networks ({{ $ipAllowlists->count() }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">Added By</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($ipAllowlists as $ip)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $ip->ip_address }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 font-medium">
                                {{ $ip->description ?? 'Primary Office Gateway' }}
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-400">
                                {{ $ip->creator?->name ?? 'System Seeder' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $ip->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $ip->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('hr-admin.ip-allowlists.toggle-status', $ip->id) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold {{ $ip->is_active ? 'text-amber-600 dark:text-amber-400 hover:underline' : 'text-emerald-600 dark:text-emerald-400 hover:underline' }}">
                                            {{ $ip->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('hr-admin.ip-allowlists.destroy', $ip->id) }}" onsubmit="return confirm('Are you sure you want to remove this IP from allowlist?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-slate-400">
                                No IP addresses authorized yet. Attendance punches will be restricted.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
