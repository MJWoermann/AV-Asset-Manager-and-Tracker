<?php

namespace App\Support;

class ConnectionTypeIcons
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return ['network', 'wireless', 'hdmi', 'sdi', 'dante', 'custom', 'analog', 'generic'];
    }

    public static function isValid(string $icon): bool
    {
        return in_array($icon, self::keys(), true);
    }
}
