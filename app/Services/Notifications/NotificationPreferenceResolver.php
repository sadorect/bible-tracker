<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Messaging\MessagingSettings;

class NotificationPreferenceResolver
{
    private const CATEGORY_GROUPS = [
        'reading_reminder' => 'reminders',
        'training_reminder' => 'reminders',
        'leader_digest' => 'leader_digest',
        'admin_digest' => 'admin_digest',
        'vacancy_alert' => 'vacancy_alert',
    ];

    private const FORCE_INBOX_GROUPS = [
        'admin_digest',
        'vacancy_alert',
    ];

    public function __construct(
        private readonly MessagingSettings $messagingSettings,
    ) {
    }

    public function deliveryFor(User $user, string $category): string
    {
        $group = $this->groupFor($category);
        $preference = $group
            ? $user->notificationPreferenceValue($group)
            : User::NOTIFICATION_DELIVERY_DEFAULT;

        if ($preference === User::NOTIFICATION_DELIVERY_DEFAULT) {
            $preference = $user->message_delivery_preference ?: $this->messagingSettings->defaultDelivery();
        }

        if (! $this->messagingSettings->emailEnabled() && ! in_array($preference, [
            User::MESSAGE_DELIVERY_INBOX,
            User::NOTIFICATION_DELIVERY_OFF,
        ], true)) {
            $preference = User::MESSAGE_DELIVERY_INBOX;
        }

        if ($group && $user->canAccessAdminPanel() && in_array($group, self::FORCE_INBOX_GROUPS, true)) {
            return match ($preference) {
                User::NOTIFICATION_DELIVERY_OFF => User::MESSAGE_DELIVERY_INBOX,
                User::MESSAGE_DELIVERY_EMAIL => User::MESSAGE_DELIVERY_BOTH,
                default => $preference,
            };
        }

        return $preference;
    }

    public function allowsDatabase(User $user, string $category): bool
    {
        return in_array($this->deliveryFor($user, $category), [
            User::MESSAGE_DELIVERY_INBOX,
            User::MESSAGE_DELIVERY_BOTH,
        ], true);
    }

    public function allowsMail(User $user, string $category): bool
    {
        return in_array($this->deliveryFor($user, $category), [
            User::MESSAGE_DELIVERY_EMAIL,
            User::MESSAGE_DELIVERY_BOTH,
        ], true) && $this->messagingSettings->emailEnabled();
    }

    private function groupFor(string $category): ?string
    {
        return self::CATEGORY_GROUPS[$category] ?? null;
    }
}
