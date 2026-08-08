@if(session('success'))
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 shadow-sm backdrop-blur transition-all flex items-start gap-3">
        <div class="rounded-lg bg-emerald-100 p-1.5 text-emerald-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-semibold text-emerald-900">Success</h4>
            <p class="text-xs text-emerald-800 mt-0.5">{{ session('success') }}</p>
        </div>
    </div>
@endif

@if(session('error') || $errors->any())
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50/90 p-4 shadow-sm backdrop-blur transition-all flex items-start gap-3">
        <div class="rounded-lg bg-rose-100 p-1.5 text-rose-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-semibold text-rose-900">Action Required</h4>
            @if(session('error'))
                <p class="text-xs text-rose-800 mt-0.5">{{ session('error') }}</p>
            @endif
            @if($errors->any())
                <ul class="mt-1.5 list-disc list-inside text-xs text-rose-800 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif

@if(session('warning'))
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/90 p-4 shadow-sm backdrop-blur transition-all flex items-start gap-3">
        <div class="rounded-lg bg-amber-100 p-1.5 text-amber-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-semibold text-amber-900">Notice</h4>
            <p class="text-xs text-amber-800 mt-0.5">{{ session('warning') }}</p>
        </div>
    </div>
@endif
