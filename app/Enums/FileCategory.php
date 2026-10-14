<?php

namespace App\Enums;

enum FileCategory: string
{
    case Manual = 'manual';
    case Quote = 'quote';
    case Photo = 'photo';
    case Floorplan = 'floorplan';
    case Invoice = 'invoice';
    case Maintenance = 'maintenance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Quote => 'Quote',
            self::Photo => 'Photo',
            self::Floorplan => 'Floorplan',
            self::Invoice => 'Invoice',
            self::Maintenance => 'Maintenance',
            self::Other => 'Other',
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
