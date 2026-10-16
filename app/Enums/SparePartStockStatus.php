<?php

namespace App\Enums;

enum SparePartStockStatus: string
{
    case InStock = 'in_stock';
    case OnOrder = 'on_order';
    case Quarantine = 'quarantine';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In stock',
            self::OnOrder => 'On order',
            self::Quarantine => 'Quarantine',
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
