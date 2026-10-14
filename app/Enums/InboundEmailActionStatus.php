<?php

namespace App\Enums;

enum InboundEmailActionStatus: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case AutoApplied = 'auto_applied';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Applied => 'Applied',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
            self::AutoApplied => 'Auto-applied',
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
