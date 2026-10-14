<?php

namespace App\Enums;

enum RuPlacementKind: string
{
    case Asset = 'asset';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Note => 'Note',
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
