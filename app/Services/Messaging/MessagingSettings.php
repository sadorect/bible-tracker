<?php

namespace App\Services\Messaging;

use App\Models\SystemSetting;
use App\Models\User;

class MessagingSettings
{
    public const KEY_DEFAULT_DELIVERY = 'messaging.default_delivery';

    public const KEY_EMAIL_ENABLED = 'messaging.email_enabled';

    public function defaultDelivery(): string
    {
        return $this->string(self::KEY_DEFAULT_DELIVERY, User::MESSAGE_DELIVERY_BOTH);
    }

    public function emailEnabled(): bool
    {
        return $this->boolean(self::KEY_EMAIL_ENABLED, true);
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );
        }
    }

    public function string(string $key, string $default): string
    {
        return SystemSetting::query()->whereKey($key)->value('value') ?? $default;
    }

    public function boolean(string $key, bool $default): bool
    {
        $value = SystemSetting::query()->whereKey($key)->value('value');

        if ($value === null) {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
