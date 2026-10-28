<?php

namespace App\Enums;

enum RuPlacementWidth: string
{
    case Full = 'full';
    case Half = 'half';
    case Third = 'third';

    public const INVALID_SLOT_MESSAGE = 'That position is not available for this width.';

    public const FULL_SLOT_MESSAGE = 'Full-width placements use a single column.';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full',
            self::Half => 'Half',
            self::Third => 'Third',
        };
    }

    public function maxSlot(): int
    {
        return match ($this) {
            self::Full => 1,
            self::Half => 2,
            self::Third => 3,
        };
    }

    public function positionLabel(int $slot): string
    {
        return match (true) {
            $this === self::Half && $slot === 2 => 'Right',
            $this === self::Third && $slot === 2 => 'Centre',
            $slot === 3 => 'Right',
            default => 'Left',
        };
    }

    public function acceptsSlot(int $slot): bool
    {
        return $slot >= 1 && $slot <= $this->maxSlot();
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
