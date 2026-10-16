<?php

namespace App\Enums;

enum SparePartKind: string
{
    case ReplacementAsset = 'replacement_asset';
    case SparePart = 'spare_part';

    public function label(): string
    {
        return match ($this) {
            self::ReplacementAsset => 'Replacement asset',
            self::SparePart => 'Spare part',
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
