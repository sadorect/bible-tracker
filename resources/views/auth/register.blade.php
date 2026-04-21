<x-guest-layout wide>
    <x-slot name="aside">
        <div class="max-w-xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/70 dark:bg-gray-800/70 border border-emerald-200/60 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-xs font-medium">
                Join the journey
            </span>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white leading-tight">
                Build a life-giving habit in Scripture.
            </h2>
            <p class="mt-3 text-gray-700 dark:text-gray-300">Create your account to start a plan, track your progress, and read with your community.</p>
            <ul class="mt-6 space-y-3 text-gray-700 dark:text-gray-300">
                <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Start with the New Testament daily journey</li>
                <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Report your reading progress each day</li>
                <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Stay encouraged by your leaders and group</li>
            </ul>
        </div>
    </x-slot>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create your account</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Join readers building daily, life-giving habits</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone_number" :value="__('Phone Number (optional)')" />
            <x-text-input id="phone_number" class="block mt-1 w-full" type="text" name="phone_number" :value="old('phone_number')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

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
        <div class="mt-4">
            <x-input-label for="captcha_answer" :value="__('Prove you\'re human')" />
            <div class="mt-1 flex items-center gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    What is <strong id="capRegA">{{ $captchaA ?? session('captcha_register_a') }}</strong> + <strong id="capRegB">{{ $captchaB ?? session('captcha_register_b') }}</strong>?
                </span>
                <button type="button" id="refreshCaptchaRegister" class="text-xs text-emerald-700 dark:text-emerald-300 hover:underline">Refresh</button>
            </div>
            <x-text-input id="captcha_answer" class="block mt-2 w-full" type="number" name="captcha_answer" :value="old('captcha_answer')" required />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700">
            {{ __('Create account') }}
        </x-primary-button>
    </form>

    <!-- Tiny benefits list below form -->
    <ul class="mt-6 text-sm text-gray-600 dark:text-gray-400 space-y-1">
        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Begin the New Testament stage</li>
        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Mark daily reading complete</li>
        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Grow with your group</li>
    </ul>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 dark:text-emerald-300 hover:underline">{{ __('Sign in') }}</a>
    </p>
</x-guest-layout>

@push('scripts')
<script>
document.getElementById('refreshCaptchaRegister')?.addEventListener('click', async () => {
    try {
        const res = await fetch('{{ route('captcha.refresh') }}?for=register', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        document.getElementById('capRegA').textContent = data.a;
        document.getElementById('capRegB').textContent = data.b;
    } catch (e) { console.warn('Failed to refresh captcha'); }
});
</script>
@endpush
