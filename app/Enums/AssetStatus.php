<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case InService = 'in_service';
    case OnEvent = 'on_event';
    case Maintenance = 'maintenance';
    case Damaged = 'damaged';
    case Disposed = 'disposed';
    case Lost = 'lost';
    case Reserved = 'reserved';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InService => 'In Service',
            self::OnEvent => 'On Event',
            self::Maintenance => 'Maintenance',
            self::Damaged => 'Damaged',
            self::Disposed => 'Disposed',
            self::Lost => 'Lost',
            self::Reserved => 'Reserved',
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
