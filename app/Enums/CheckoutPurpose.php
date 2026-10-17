<?php

namespace App\Enums;

enum CheckoutPurpose: string
{
    case Hire = 'hire';
    case Repair = 'repair';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hire => 'Hire',
            self::Repair => 'Repair',
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
