<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Count = 'count';
    case Restock = 'restock';
    case Breakage = 'breakage';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Count',
            self::Restock => 'Restock',
            self::Breakage => 'Breakage',
            self::Adjustment => 'Adjustment',
        };
    }

    public function auditAction(): string
    {
        return match ($this) {
            self::Count => 'stock_count',
            self::Restock => 'stock_restock',
            self::Breakage => 'stock_breakage',
            self::Adjustment => 'stock_adjustment',
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
