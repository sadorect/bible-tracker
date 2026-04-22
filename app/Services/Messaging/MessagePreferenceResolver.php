<?php

namespace App\Services\Messaging;

use App\Models\User;

class MessagePreferenceResolver
{
    public function __construct(
        private readonly MessagingSettings $settings,
    ) {
    }

    public function resolveFor(User $recipient): string
    {
        $preference = $recipient->message_delivery_preference_locked && $recipient->message_delivery_preference
            ? $recipient->message_delivery_preference
            : ($recipient->message_delivery_preference ?: $this->settings->defaultDelivery());

        if (! $this->settings->emailEnabled() && $preference !== User::MESSAGE_DELIVERY_INBOX) {
            return User::MESSAGE_DELIVERY_INBOX;
        }

        return $preference;
    }

    public function allowsInbox(string $preference): bool
    {
        return in_array($preference, [
            User::MESSAGE_DELIVERY_INBOX,
            User::MESSAGE_DELIVERY_BOTH,
        ], true);
    }

    public function allowsEmail(string $preference): bool
    {
        return in_array($preference, [
            User::MESSAGE_DELIVERY_EMAIL,
            User::MESSAGE_DELIVERY_BOTH,
        ], true) && $this->settings->emailEnabled();
    }
}
