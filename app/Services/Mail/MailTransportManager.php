<?php

namespace App\Services\Mail;

use App\Contracts\MailTransport;
use App\Support\EmailIntegrationSettings;
use InvalidArgumentException;

class MailTransportManager
{
    public function __construct(
        private SmtpTransport $smtp,
        private OutlookGraphTransport $outlook,
    ) {}

    public function driver(?string $driver = null): MailTransport
    {
        $driver ??= EmailIntegrationSettings::get()['driver'];

        return match ($driver) {
            'gmail', 'smtp' => $this->smtp,
            'outlook' => $this->outlook,
            default => throw new InvalidArgumentException('Unsupported mail driver: '.$driver),
        };
    }
}
