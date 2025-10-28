<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Confirm your address</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">We’ve sent a verification link. Click it to activate your account and start your plan.</p>
            <div class="mt-6 rounded-2xl bg-white/70 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Didn’t get it? Check your spam folder or resend below.</p>
            </div>
        </div>
    </x-slot>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Verify your email</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">We sent a verification link to your email address</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="bg-emerald-600 hover:bg-emerald-700">
                    {{ __('Resend verification email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
