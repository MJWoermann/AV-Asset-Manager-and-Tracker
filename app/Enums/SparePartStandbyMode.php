<?php

namespace App\Enums;

enum SparePartStandbyMode: string
{
    case None = 'none';
    case Hot = 'hot';
    case Cold = 'cold';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Hot => 'Hot spare',
            self::Cold => 'Cold spare',
        };
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
