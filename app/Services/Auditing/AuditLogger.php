<?php

namespace App\Services\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        string $event,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?string $description = null,
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        $auditLog = new AuditLog([
            'event' => $event,
            'subject_label' => $subject ? $this->subjectLabel($subject) : null,
            'description' => $description,
            'metadata' => $this->cleanMetadata($metadata),
            'route_name' => $this->routeName($request),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);

        if ($actor) {
            $auditLog->actor()->associate($actor);
        }

        if ($subject) {
            $auditLog->subject()->associate($subject);
        }

        $auditLog->save();

        return $auditLog;
    }

    private function cleanMetadata(array $metadata): array
    {
        $cleaned = [];

        foreach ($metadata as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            $cleaned[$key] = $this->normalizeValue($value);
        }

        ksort($cleaned);

        return $cleaned;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }

            return $normalized;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Model) {
            return [
                'type' => $value::class,
                'id' => $value->getKey(),
                'label' => $this->subjectLabel($value),
            ];
        }

        if (is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        return (string) $value;
    }

    private function subjectLabel(Model $subject): string
    {
        foreach (['name', 'title', 'label'] as $attribute) {
            if (filled($subject->getAttribute($attribute))) {
                return (string) $subject->getAttribute($attribute);
            }
        }

        if (method_exists($subject, 'displayPath')) {
            return (string) $subject->displayPath();
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    private function routeName(?Request $request): ?string
    {
        return $request?->route()?->getName();
    }
}
