<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Extra step for safety</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">We ask for your password again to protect sensitive actions.</p>
            <div class="mt-6 rounded-2xl bg-white/70 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">You’ll only see this when it really matters.</p>
            </div>
        </div>
    </x-slot>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Confirm your password</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">For your security, please confirm to continue</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
