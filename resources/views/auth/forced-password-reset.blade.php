<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Time for a new password</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">Your account is still using a default password that was set up during installation. Please choose a private one before continuing.</p>
            <div class="mt-6 rounded-2xl bg-white/70 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Pick something you don’t use anywhere else. You’ll only need to do this once.</p>
            </div>
        </div>
    </x-slot>

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Choose a new password</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">This is required before you can use your account</p>
    </div>

    <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 p-4">
        <p class="text-sm text-amber-800 dark:text-amber-200">
            {{ __('Your current password is a known default. Set a new one to keep your account secure.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.forced.update') }}">
        @csrf
        @method('PUT')

        <!-- New Password -->
        <div>
            <x-input-label for="password" :value="__('New password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
                {{ __('Save new password') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-200">
            {{ __('Log out instead') }}
        </button>
    </form>
</x-guest-layout>
