<?php

namespace App\Enums;

enum FileSourceType: string
{
    case Upload = 'upload';
    case Url = 'url';

    public function label(): string
    {
        return match ($this) {
            self::Upload => 'Upload',
            self::Url => 'URL',
        };
    }
}
