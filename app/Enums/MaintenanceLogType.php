<?php

namespace App\Enums;

enum MaintenanceLogType: string
{
    case Fault = 'fault';
    case Service = 'service';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Fault => 'Fault',
            self::Service => 'Service',
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
