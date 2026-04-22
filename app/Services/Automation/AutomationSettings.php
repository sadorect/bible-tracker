<?php

namespace App\Services\Automation;

use App\Models\SystemSetting;

class AutomationSettings
{
    public const KEY_MEMBER_READING_REMINDERS = 'automation.member_reading_reminders';

    public const KEY_MEMBER_TRAINING_REMINDERS = 'automation.member_training_reminders';

    public const KEY_LEADER_DIGESTS = 'automation.leader_digests';

    public const KEY_ADMIN_DIGESTS = 'automation.admin_digests';

    public const KEY_VACANCY_ALERTS = 'automation.vacancy_alerts';

    public const KEY_EMAIL_ENABLED = 'automation.email_enabled';

    public const KEY_LIFECYCLE_AUTOMATION = 'automation.lifecycle_automation_enabled';

    public const KEY_LAST_RUN_AT = 'automation.last_run_at';

    public function all(): array
    {
        return [
            'member_reading_reminders' => $this->memberReadingRemindersEnabled(),
            'member_training_reminders' => $this->memberTrainingRemindersEnabled(),
            'leader_digests' => $this->leaderDigestsEnabled(),
            'admin_digests' => $this->adminDigestsEnabled(),
            'vacancy_alerts' => $this->vacancyAlertsEnabled(),
            'email_enabled' => $this->emailEnabled(),
            'lifecycle_automation_enabled' => $this->lifecycleAutomationEnabled(),
            'last_run_at' => $this->lastRunAt(),
        ];
    }

    public function memberReadingRemindersEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_MEMBER_READING_REMINDERS, true);
    }

    public function memberTrainingRemindersEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_MEMBER_TRAINING_REMINDERS, true);
    }

    public function leaderDigestsEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_LEADER_DIGESTS, true);
    }

    public function adminDigestsEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_ADMIN_DIGESTS, true);
    }

    public function vacancyAlertsEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_VACANCY_ALERTS, true);
    }

    public function emailEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_EMAIL_ENABLED, true);
    }

    public function lifecycleAutomationEnabled(): bool
    {
        return $this->booleanOrDefault(self::KEY_LIFECYCLE_AUTOMATION, true);
    }

    public function lastRunAt(): ?string
    {
        return SystemSetting::query()->whereKey(self::KEY_LAST_RUN_AT)->value('value');
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')],
            );
        }
    }

    private function booleanOrDefault(string $key, bool $default): bool
    {
        $value = SystemSetting::query()->whereKey($key)->value('value');

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
