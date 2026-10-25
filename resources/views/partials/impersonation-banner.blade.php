@php
    $impersonatorId = session(\App\Http\Controllers\Admin\ImpersonationController::IMPERSONATOR_KEY);
    $impersonator = $impersonatorId ? \App\Models\User::find($impersonatorId) : null;
@endphp

@if($impersonator)
    <div class="sticky top-0 z-50 border-b border-amber-500 bg-amber-100 px-4 py-3 text-sm text-amber-900 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-screen-2xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-center gap-2 font-medium">
                <i class="fas fa-user-secret"></i>
                You are viewing the app as <strong>{{ auth()->user()?->name }}</strong>.
                Signed in as {{ $impersonator->name }}.
            </p>
            <form method="POST" action="{{ route('impersonation.stop') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                    Return to my account
                </button>
            </form>
        </div>
    </div>
@endif
