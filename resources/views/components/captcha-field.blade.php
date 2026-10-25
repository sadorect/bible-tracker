{{--
    Anti-abuse field for the public auth forms.

    Renders Cloudflare Turnstile when it is configured, and falls back to the
    in-house image CAPTCHA otherwise. Callers do not need to know which is
    active — both submit under the same field name, and both are checked by
    CaptchaService::verify().

    Also emits the honeypot input. Together with the per-email rate limiting in
    LoginRequest, that gives three independent layers on every form.

    Usage:  <x-captcha-field />
--}}
@props([
    'inputName' => 'captcha',
    'inputId' => 'captcha',
])

@php($captcha = app(\App\Services\CaptchaService::class))

{{-- Honeypot: hidden from people, irresistible to naive bots. Any value fails
     validation. Kept out of the tab order and off-screen rather than
     display:none, which some bots specifically skip. --}}
<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
    <label for="website">Website</label>
    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
</div>

@if ($captcha->enabled())
    <div class="mt-4">
        @if ($captcha->usesTurnstile())

            @once
                @push('scripts')
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
                    <script>
                        document.addEventListener('alpine:init', () => {
                            Alpine.data('turnstileField', (config) => ({
                                widgetId: null,

                                init() {
                                    this.renderWhenReady();

                                    // A Turnstile token is single-use. Any submit spends
                                    // the one we are holding, so queue a fresh challenge
                                    // as soon as the payload has been built — otherwise a
                                    // second attempt after a failed login would be
                                    // rejected for a stale token rather than the real
                                    // reason.
                                    this.$el.closest('form')?.addEventListener('submit', () => {
                                        setTimeout(() => this.reset(), 0);
                                    });
                                },

                                renderWhenReady() {
                                    if (! window.turnstile) {
                                        setTimeout(() => this.renderWhenReady(), 100);

                                        return;
                                    }

                                    this.widgetId = window.turnstile.render(this.$refs.widget, {
                                        sitekey: config.siteKey,
                                        theme: 'auto',
                                        action: config.action,
                                        'refresh-expired': 'auto',
                                        callback: (token) => this.store(token),
                                        'expired-callback': () => this.store(''),
                                        'error-callback': () => this.store(''),
                                    });
                                },

                                reset() {
                                    if (this.widgetId !== null && window.turnstile) {
                                        window.turnstile.reset(this.widgetId);
                                    }
                                },

                                store(token) {
                                    if (this.$refs.input) {
                                        this.$refs.input.value = token;
                                    }
                                },
                            }));
                        });
                    </script>
                @endpush
            @endonce

            <div x-data="turnstileField({
                    siteKey: @js($captcha->siteKey()),
                    action: @js(Str::slug(request()->path() ?: 'form')),
                })">
                <div x-ref="widget"></div>
                <input type="hidden" name="{{ $inputName }}" x-ref="input">

                <noscript>
                    <p class="mt-2 text-sm text-red-600">
                        {{ __('JavaScript is required to complete the security check on this form.') }}
                    </p>
                </noscript>

                <x-input-error :messages="$errors->get($inputName)" class="mt-2" />
            </div>

        @else

            <div x-data="{ src: '{{ route('captcha.image') }}?v=' + Date.now() }">
                <x-input-label for="{{ $inputId }}" :value="__('Prove you\'re human')" />

                <div class="mt-2 flex items-center gap-3">
                    <img
                        :src="src"
                        alt="{{ __('Security code') }}"
                        class="h-12 w-40 select-none rounded border border-gray-300 dark:border-gray-700 object-cover"
                        draggable="false"
                    >
                    <button
                        type="button"
                        @click="src = '{{ route('captcha.image') }}?v=' + Date.now()"
                        class="text-sm font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 transition"
                        title="{{ __('Load a new code') }}"
                    >
                        {{ __('Refresh') }}
                    </button>
                </div>

                <x-text-input
                    id="{{ $inputId }}"
                    name="{{ $inputName }}"
                    type="text"
                    class="block mt-3 w-full"
                    required
                    autocomplete="off"
                    maxlength="6"
                    placeholder="{{ __('e.g. A3BXZ') }}"
                />
                <x-input-error :messages="$errors->get($inputName)" class="mt-2" />
            </div>

        @endif
    </div>
@endif
