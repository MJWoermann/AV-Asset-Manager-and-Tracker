<?php

namespace App\Enums;

enum SparePartMovementType: string
{
    case Use = 'use';
    case Replace = 'replace';
    case Restock = 'restock';
    case Adjustment = 'adjustment';
    case Count = 'count';

    public function label(): string
    {
        return match ($this) {
            self::Use => 'Use',
            self::Replace => 'Replace',
            self::Restock => 'Restock',
            self::Adjustment => 'Adjustment',
            self::Count => 'Count',
        };
    }

    public function requiresAssetAndReason(): bool
    {
        return in_array($this, [self::Use, self::Replace], true);
    }

    public function auditAction(): string
    {
        return match ($this) {
            self::Use => 'spare_use',
            self::Replace => 'spare_replace',
            self::Restock => 'spare_restock',
            self::Adjustment => 'spare_adjustment',
            self::Count => 'spare_count',
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
