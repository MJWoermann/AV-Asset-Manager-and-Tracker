<?php

namespace App\Enums;

enum RuPlacementFace: string
{
    case Front = 'front';
    case Rear = 'rear';

    public function label(): string
    {
        return match ($this) {
            self::Front => 'Front',
            self::Rear => 'Rear',
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
