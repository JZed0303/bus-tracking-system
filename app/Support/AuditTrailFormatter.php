<?php

namespace App\Support;

class AuditTrailFormatter
{
    public static function payload(array $values = []): array
    {
        $rows = [];

        foreach ($values as $key => $value) {
            $rows[] = [
                'label' => self::label((string) $key),
                'value' => self::value($value),
            ];
        }

        return $rows;
    }

    public static function changes(array $oldValues = [], array $newValues = [], string $event = ''): array
    {
        $rows = [];
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($keys as $key) {
            $hasOld = array_key_exists($key, $oldValues);
            $hasNew = array_key_exists($key, $newValues);

            $old = $hasOld ? $oldValues[$key] : null;
            $new = $hasNew ? $newValues[$key] : null;

            if (self::isUpdateEvent($event) && $hasOld && $hasNew && self::sameValue($old, $new)) {
                continue;
            }

            if ($hasOld && $hasNew && self::sameValue($old, $new)) {
                continue;
            }

            $rows[] = [
                'label' => self::label((string) $key),
                'old' => self::value($hasOld ? $old : null),
                'new' => self::value($hasNew ? $new : null),
            ];
        }

        return $rows;
    }

    private static function label(string $field): string
    {
        return ucwords(str_replace('_', ' ', trim($field)));
    }

    private static function value(mixed $value): string
    {
        if ($value === null) {
            return 'Empty';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value)) {
            return trim($value) === '' ? 'Empty' : $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return self::formatArray($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $text = (string) $value;
                return trim($text) === '' ? 'Empty' : $text;
            }

            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: 'N/A';
        }

        return 'N/A';
    }

    private static function formatArray(array $value): string
    {
        if ($value === []) {
            return 'Empty';
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);

        if (! $isAssoc) {
            return implode(', ', array_map(fn ($item) => self::value($item), $value));
        }

        $parts = [];
        foreach ($value as $key => $item) {
            $parts[] = self::label((string) $key) . ': ' . self::value($item);
        }

        return implode(' | ', $parts);
    }

    private static function sameValue(mixed $old, mixed $new): bool
    {
        return json_encode($old) === json_encode($new);
    }

    private static function isUpdateEvent(string $event): bool
    {
        $event = strtolower(trim($event));
        return $event === 'updated' || str_contains($event, 'updated');
    }
}
