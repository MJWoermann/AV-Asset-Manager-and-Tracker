<?php

namespace App\Enums;

enum SparePartSeverity: string
{
    case Critical = 'critical';
    case Severe = 'severe';
    case Moderate = 'moderate';
    case Minor = 'minor';
    case Passive = 'passive';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::Severe => 'Severe',
            self::Moderate => 'Moderate',
            self::Minor => 'Minor',
            self::Passive => 'Passive',
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
