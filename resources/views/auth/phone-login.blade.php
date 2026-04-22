<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Phone sign in</h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">Use your phone number to access your plans and progress quickly.</p>
        </div>
    </x-slot>

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sign in with phone</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Enter your phone number and password</p>
    </div>

    <form class="space-y-4" action="{{ route('phone.login') }}" method="POST">
        @csrf
        <div>
            <x-input-label for="phone_number" :value="__('Phone Number')" />
            <x-text-input id="phone_number" name="phone_number" type="text" class="block mt-1 w-full" required />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Simple Captcha -->
        <div class="mt-2">
            <x-input-label for="captcha_answer" :value="__('Prove you\'re human')" />
            <div class="mt-1 flex items-center gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    What is <strong id="capA">{{ $captchaA ?? session('captcha_phone_a') }}</strong> + <strong id="capB">{{ $captchaB ?? session('captcha_phone_b') }}</strong>?
                </span>
                <button type="button" id="refreshCaptchaPhone" class="text-xs text-emerald-700 dark:text-emerald-300 hover:underline">Refresh</button>
            </div>
            <x-text-input id="captcha_answer" class="block mt-2 w-full" type="number" name="captcha_answer" :value="old('captcha_answer')" required />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>
</x-guest-layout>

@push('scripts')
<script>
document.getElementById('refreshCaptchaPhone')?.addEventListener('click', async () => {
    try {
        const res = await fetch('{{ route('captcha.refresh') }}?for=phone', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        document.getElementById('capA').textContent = data.a;
        document.getElementById('capB').textContent = data.b;
    } catch (e) { console.warn('Failed to refresh captcha'); }
});
</script>
@endpush
