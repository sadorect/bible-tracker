<div class="space-y-6">
    <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">{{ $readingPlan->type_label }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isUsable ? 'bg-emerald-100 text-emerald-700' : ($isRevoked ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                {{ $isUsable ? 'Enrollment open' : ($isRevoked ? 'Link revoked' : 'Link expired') }}
            </span>
        </div>
        <h1 class="mt-4 text-3xl font-semibold text-slate-900">{{ $readingPlan->name }}</h1>
        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $readingPlan->description }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Cadence</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->cadence_description }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Commences</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->start_date?->format('M d, Y') ?? 'TBD' }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Training days</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->training_days }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Link expiry</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $invite->expires_at?->format('M d, Y g:i A') ?? 'Never' }}</p>
            </div>
        </div>

        @if(!$isUsable)
            <div class="mt-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                This enrollment link is no longer available. Please ask an admin for a fresh invitation.
            </div>
        @elseif($user)
            <div class="mt-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                Signed in as <strong>{{ $user->name }}</strong>.
                @if($existingParticipationCount > 0)
                    You have already participated in this plan {{ $existingParticipationCount }} time(s). Accepting this link starts a new participation cycle on this profile.
                @else
                    Accepting this link will enroll this profile into the plan.
                @endif
            </div>
        @endif
    </div>

    <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Next step</p>
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Choose how to continue</h2>

        <div class="mt-6 flex flex-col gap-3">
            @if(!$isUsable)
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Return home
                </a>
            @elseif($user)
                <form method="POST" action="{{ route('reading-plan-invites.accept', $invite->token) }}">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Use my current profile
                    </button>
                </form>

                <a href="{{ route('reading-plan-invites.register-fresh', $invite->token) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Register a fresh profile instead
                </a>
            @else
                <a href="{{ route('reading-plan-invites.login', $invite->token) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Use an existing account
                </a>
                <a href="{{ route('reading-plan-invites.register-fresh', $invite->token) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Register a fresh profile
                </a>
            @endif
        </div>
    </div>
</div>
