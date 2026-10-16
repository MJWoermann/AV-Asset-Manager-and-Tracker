<?php

namespace App\Enums;

enum RigEventStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case SignedOff = 'signed_off';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In progress',
            self::SignedOff => 'Signed off',
            self::Archived => 'Archived',
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
};
