@php
    $inviteContent = view('reading-plan-invites.partials.content', [
        'invite' => $invite,
        'readingPlan' => $readingPlan,
        'user' => $user,
        'isUsable' => $isUsable,
        'isExpired' => $isExpired,
        'isRevoked' => $isRevoked,
        'existingParticipationCount' => $existingParticipationCount,
    ])->render();
@endphp

@if($layoutComponent === 'guest-layout')
    <x-guest-layout wide>
        <x-slot name="aside">
            <div class="max-w-xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200/60 bg-white/70 px-3 py-1 text-xs font-medium text-emerald-700">
                    Open enrollment
                </span>
                <h2 class="mt-4 text-3xl font-extrabold leading-tight text-gray-900">
                    You have been invited to join {{ $readingPlan->name }}.
                </h2>
                <p class="mt-3 text-gray-700">Use your current profile or create a fresh one. Returning readers can start this journey again without losing earlier participation records.</p>
            </div>
        </x-slot>

        {!! $inviteContent !!}
    </x-guest-layout>
@else
    <x-dynamic-component :component="$layoutComponent">
        <x-slot name="header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Guest Enrollment</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Join {{ $readingPlan->name }}</h1>
            </div>
        </x-slot>

        {!! $inviteContent !!}
    </x-dynamic-component>
@endif
