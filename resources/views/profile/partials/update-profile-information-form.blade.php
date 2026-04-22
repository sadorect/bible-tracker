<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="message_delivery_preference" :value="__('Message Delivery Preference')" />
            <select
                id="message_delivery_preference"
                name="message_delivery_preference"
                class="mt-1 block w-full rounded-xl border-gray-300"
                {{ $user->message_delivery_preference_locked ? 'disabled' : '' }}
            >
                <option value="">{{ __('Use admin default') }}</option>
                @foreach(\App\Models\User::messageDeliveryOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('message_delivery_preference', $user->message_delivery_preference) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if($user->message_delivery_preference_locked)
                <p class="mt-2 text-sm text-gray-500">{{ __('Your delivery preference is locked by an administrator.') }}</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('message_delivery_preference')" />
        </div>

        @if(\App\Support\SchemaCapabilities::supportsNotificationPreferences())
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Alert preferences</h3>
                    <p class="mt-1 text-sm text-gray-600">Choose how reminders and digests reach you.</p>
                </div>

                <div class="mt-4 grid gap-4">
                    @foreach(\App\Models\User::notificationPreferenceGroups() as $group => $label)
                        <div>
                            <x-input-label :for="'notification_preferences_'.$group" :value="__($label)" />
                            <select
                                id="notification_preferences_{{ $group }}"
                                name="notification_preferences[{{ $group }}]"
                                class="mt-1 block w-full rounded-xl border-gray-300"
                            >
                                @foreach(\App\Models\User::notificationDeliveryOptions() as $value => $optionLabel)
                                    <option value="{{ $value }}" {{ old('notification_preferences.'.$group, $user->notificationPreferenceValue($group)) === $value ? 'selected' : '' }}>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('notification_preferences.'.$group)" />
                        </div>
                    @endforeach
                </div>

                @if($user->canAccessAdminPanel())
                    <p class="mt-4 text-sm text-gray-500">Admin digests and vacancy alerts always stay in your inbox.</p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
