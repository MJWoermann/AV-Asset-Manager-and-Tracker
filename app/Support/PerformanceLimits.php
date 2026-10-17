<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Soft resource limits for import / export / compare / backup.
 *
 * Resolution order: settings table override → config/performance.php (.env).
 */
class PerformanceLimits
{
    public const SETTING_KEY = 'performance_limits';

    /**
     * @return array<string, int|bool>
     */
    public static function defaults(): array
    {
        return [
            'import_max_rows' => (int) config('performance.import_max_rows', 10000),
            'import_commit_chunk' => (int) config('performance.import_commit_chunk', 250),
            'export_max_rows' => (int) config('performance.export_max_rows', 25000),
            'compare_max_list_items' => (int) config('performance.compare_max_list_items', 15000),
            'backup_default_keep' => (int) config('performance.backup_default_keep', 7),
            'backup_force_queue' => (bool) config('performance.backup_force_queue', true),
            'migrate_force_queue' => (bool) config('performance.migrate_force_queue', true),
            'throttle_import_per_minute' => (int) config('performance.throttle_import_per_minute', 10),
            'throttle_export_per_minute' => (int) config('performance.throttle_export_per_minute', 10),
            'throttle_compare_per_minute' => (int) config('performance.throttle_compare_per_minute', 10),
            'throttle_backup_per_minute' => (int) config('performance.throttle_backup_per_minute', 3),
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    /**
     * @return array<string, int|bool>
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $stored = Setting::getValue(self::SETTING_KEY);

        if (! is_array($stored)) {
            return $defaults;
        }

        $merged = $defaults;
        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }

            $merged[$key] = self::castValue($key, $stored[$key]);
        }

        return $merged;
    }

    public static function get(string $key): int|bool
    {
        $all = self::all();

        if (! array_key_exists($key, $all)) {
            throw new InvalidArgumentException("Unknown performance limit [{$key}].");
        }

        return $all[$key];
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function bool(string $key): bool
    {
        return (bool) self::get($key);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, int|bool>
     */
    public static function put(array $overrides): array
    {
        $current = self::all();
        $defaults = self::defaults();

        foreach ($overrides as $key => $value) {
            if (! array_key_exists($key, $defaults)) {
                throw new InvalidArgumentException("Unknown performance limit [{$key}].");
            }

            $current[$key] = self::castValue($key, $value);
            self::assertValid($key, $current[$key]);
        }

        Setting::putValue(self::SETTING_KEY, $current);

        return $current;
    }

    public static function reset(?string $key = null): array
    {
        if ($key === null) {
            Setting::query()->where('key', self::SETTING_KEY)->delete();

            return self::defaults();
        }

        if (! array_key_exists($key, self::defaults())) {
            throw new InvalidArgumentException("Unknown performance limit [{$key}].");
        }

        $current = self::all();
        $current[$key] = self::defaults()[$key];
        Setting::putValue(self::SETTING_KEY, $current);

        return $current;
    }

    /**
     * Operator guidance when a limit is hit.
     */
    public static function raiseHint(string $key): string
    {
        return "Raise with: php artisan app:performance-limits set {$key} <value>";
    }

    protected static function castValue(string $key, mixed $value): int|bool
    {
        if (str_ends_with($key, '_force_queue')) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));

                return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
            }

            return (bool) $value;
        }

        return max(0, (int) $value);
    }

    protected static function assertValid(string $key, int|bool $value): void
    {
        if (str_ends_with($key, '_force_queue')) {
            return;
        }

        $int = (int) $value;

        if ($key === 'backup_default_keep' && ($int < 1 || $int > 100)) {
            throw new InvalidArgumentException('backup_default_keep must be between 1 and 100.');
        }

        if ($key === 'import_commit_chunk' && ($int < 1 || $int > 5000)) {
            throw new InvalidArgumentException('import_commit_chunk must be between 1 and 5000.');
        }

        if (str_starts_with($key, 'throttle_') && ($int < 1 || $int > 1000)) {
            throw new InvalidArgumentException("{$key} must be between 1 and 1000.");
        }

        if (in_array($key, ['import_max_rows', 'export_max_rows', 'compare_max_list_items'], true) && $int < 1) {
            throw new InvalidArgumentException("{$key} must be at least 1.");
        }
    }
}
