<?php

namespace App\Enums;

enum InboundEmailRuleAction: string
{
    case CreateLog = 'create_log';
    case AppendLog = 'append_log';

    public function label(): string
    {
        return match ($this) {
            self::CreateLog => 'Create maintenance log',
            self::AppendLog => 'Append maintenance log',
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
