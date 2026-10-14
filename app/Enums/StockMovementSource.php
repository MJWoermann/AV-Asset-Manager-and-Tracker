<?php

namespace App\Enums;

enum StockMovementSource: string
{
    case Web = 'web';
    case GuestQr = 'guest_qr';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::GuestQr => 'Guest QR',
            self::Api => 'API',
        };
    }
}
