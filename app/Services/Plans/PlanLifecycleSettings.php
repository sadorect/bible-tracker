<?php

namespace App\Services\Plans;

use App\Models\SystemSetting;

class PlanLifecycleSettings
{
    public const KEY_MAX_LIVE_NEW_TESTAMENT = 'plans.max_live_new_testament';

    public const KEY_MAX_LIVE_OLD_TESTAMENT = 'plans.max_live_old_testament';

    public const KEY_MAX_LIVE_TOTAL = 'plans.max_live_total';

    public function maxLiveNewTestament(): ?int
    {
        return $this->integerOrNull(self::KEY_MAX_LIVE_NEW_TESTAMENT, 1);
    }

    public function maxLiveOldTestament(): ?int
    {
        return $this->integerOrNull(self::KEY_MAX_LIVE_OLD_TESTAMENT, 1);
    }

    public function maxLiveTotal(): ?int
    {
        return $this->integerOrNull(self::KEY_MAX_LIVE_TOTAL, 2);
    }

    public function all(): array
    {
        return [
            'max_live_new_testament' => $this->maxLiveNewTestament(),
            'max_live_old_testament' => $this->maxLiveOldTestament(),
            'max_live_total' => $this->maxLiveTotal(),
        ];
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? '' : (string) $value],
            );
        }
    }

    private function integerOrNull(string $key, ?int $default = null): ?int
    {
        $value = SystemSetting::query()->whereKey($key)->value('value');

        if ($value === null) {
            return $default;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        return max((int) $trimmed, 0);
    }
}
