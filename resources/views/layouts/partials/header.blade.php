@php
    $user = Auth::user();
    $role = $user?->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user?->role;
@endphp

<header class="h-16 bg-white/95 backdrop-blur border-b border-slate-200/80 px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
    <!-- Left: Title or Breadcrumbs -->
    <div class="flex items-center gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-800 tracking-tight leading-tight">
                @yield('page-title', 'HRM Portal')
            </h2>
            <p class="text-xs text-slate-500 font-medium">
                {{ now()->format('l, F j, Y') }} &bull; Approved Office Network Check
            </p>
        </div>
    </div>

    <!-- Right: Status / Profile -->
    <div class="flex items-center gap-3">
        <!-- Role Indicator Pill -->
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
            @if($role === 'super_admin') bg-purple-50 text-purple-700 border border-purple-200
            @elseif($role === 'hr_admin') bg-blue-50 text-blue-700 border border-blue-200
            @else bg-emerald-50 text-emerald-700 border border-emerald-200 @endif">
            <span class="h-2 w-2 rounded-full @if($role === 'super_admin') bg-purple-500 @elseif($role === 'hr_admin') bg-blue-500 @else bg-emerald-500 @endif animate-pulse"></span>
            @if($role === 'super_admin') Super Admin @elseif($role === 'hr_admin') HR Admin @else Employee @endif
        </span>

        <!-- Quick Time Clock -->
        <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-lg bg-slate-100/80 text-xs font-mono font-medium text-slate-700">
            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="header-live-clock">{{ now()->format('H:i') }}</span>
        </div>

        <!-- User Dropdown Mini Card -->
        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
            <div class="h-8 w-8 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
            </div>
            <div class="hidden md:block text-left">
                <p class="text-xs font-bold text-slate-800 leading-tight">{{ $user?->name ?? 'User' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ $user?->username ?? 'user' }}</p>
            </div>
        </div>
    </div>
</header>
