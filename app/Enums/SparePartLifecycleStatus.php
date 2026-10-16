<?php

namespace App\Enums;

enum SparePartLifecycleStatus: string
{
    case Active = 'active';
    case EndOfLife = 'end_of_life';
    case Superseded = 'superseded';
    case Obsolete = 'obsolete';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::EndOfLife => 'End of life',
            self::Superseded => 'Superseded',
            self::Obsolete => 'Obsolete',
        };
    }

    public function isNonActive(): bool
    {
        return $this !== self::Active;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
