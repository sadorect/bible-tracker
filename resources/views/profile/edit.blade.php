<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Account Settings</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Profile</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Your account</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Manage your identity, security, and account access.</h3>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                Keep your details current so leaders can identify you correctly, and update your password regularly to keep your account secure.
            </p>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-[2rem] border border-rose-200 bg-white p-6 shadow-xl shadow-rose-900/5 lg:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
