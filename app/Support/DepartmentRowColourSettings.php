<?php

namespace App\Support;

use App\Models\Setting;

class DepartmentRowColourSettings
{
    public const KEY = 'department_row_colours';

    /**
     * @return array{enabled: bool}
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
        ];
    }

    /**
     * @return array{enabled: bool}
     */
    public static function get(): array
    {
        $stored = Setting::getValue(self::KEY);

        return is_array($stored)
            ? array_merge(self::defaults(), [
                'enabled' => (bool) ($stored['enabled'] ?? false),
            ])
            : self::defaults();
    }

    public static function enabled(): bool
    {
        return self::get()['enabled'];
    }

    public static function put(bool $enabled): void
    {
        Setting::putValue(self::KEY, [
            'enabled' => $enabled,
        ]);
    }
}
