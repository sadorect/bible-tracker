<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Secure your account</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">Choose a strong password you haven’t used before on this site.</p>
            <ul class="mt-6 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> 8+ characters recommended</li>
                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Mix letters and numbers</li>
            </ul>
        </div>
    </x-slot>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Choose a new password</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Keep it secure and unique</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Simple Captcha -->
        <div class="mt-2">
            <x-input-label for="captcha_answer" :value="__('Prove you\'re human')" />
            <div class="mt-1 flex items-center gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    What is <strong id="capPwResetA">{{ $captchaA ?? session('captcha_pwreset_a') }}</strong> + <strong id="capPwResetB">{{ $captchaB ?? session('captcha_pwreset_b') }}</strong>?
                </span>
                <button type="button" id="refreshCaptchaPwReset" class="text-xs text-emerald-700 dark:text-emerald-300 hover:underline">Refresh</button>
            </div>
            <x-text-input id="captcha_answer" class="block mt-2 w-full" type="number" name="captcha_answer" :value="old('captcha_answer')" required />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
            {{ __('Reset password') }}
        </x-primary-button>
    </form>
</x-guest-layout>

@push('scripts')
<script>
document.getElementById('refreshCaptchaPwReset')?.addEventListener('click', async () => {
    try {
        const res = await fetch('{{ route('captcha.refresh') }}?for=password-reset', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        document.getElementById('capPwResetA').textContent = data.a;
        document.getElementById('capPwResetB').textContent = data.b;
    } catch (e) { console.warn('Failed to refresh captcha'); }
});
</script>
@endpush
