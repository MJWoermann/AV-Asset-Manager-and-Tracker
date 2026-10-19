<?php

namespace App\Enums;

enum LocationType: string
{
    case Site = 'site';
    case Building = 'building';
    case Level = 'level';
    case Room = 'room';
    case Rack = 'rack';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site',
            self::Building => 'Building',
            self::Level => 'Level',
            self::Room => 'Room',
            self::Rack => 'Rack',
        };
    }
}
