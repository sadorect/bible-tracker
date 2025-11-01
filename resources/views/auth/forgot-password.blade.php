<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">We’ll help you get back in</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">Enter your email and we’ll send a secure link to reset your password.</p>
            <div class="mt-6 rounded-2xl bg-white/70 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tip: Use a strong password—at least 8 characters with a mix of letters and numbers.</p>
            </div>
        </div>
    </x-slot>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reset your password</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Enter your email and we’ll send you a reset link</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Simple Captcha -->
        <div class="mt-2">
            <x-input-label for="captcha_answer" :value="__('Prove you\'re human')" />
            <div class="mt-1 flex items-center gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    What is <strong id="capPwReqA">{{ $captchaA ?? session('captcha_pwreq_a') }}</strong> + <strong id="capPwReqB">{{ $captchaB ?? session('captcha_pwreq_b') }}</strong>?
                </span>
                <button type="button" id="refreshCaptchaPwReq" class="text-xs text-emerald-700 dark:text-emerald-300 hover:underline">Refresh</button>
            </div>
            <x-text-input id="captcha_answer" class="block mt-2 w-full" type="number" name="captcha_answer" :value="old('captcha_answer')" required />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
            {{ __('Send reset link') }}
        </x-primary-button>
    </form>
</x-guest-layout>

@push('scripts')
<script>
document.getElementById('refreshCaptchaPwReq')?.addEventListener('click', async () => {
    try {
        const res = await fetch('{{ route('captcha.refresh') }}?for=password-request', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        document.getElementById('capPwReqA').textContent = data.a;
        document.getElementById('capPwReqB').textContent = data.b;
    } catch (e) { console.warn('Failed to refresh captcha'); }
});
</script>
@endpush
