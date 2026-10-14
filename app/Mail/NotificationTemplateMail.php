<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NotificationTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $toAddresses
     */
    public function __construct(
        public array $toAddresses,
        public string $emailSubject,
        public string $bodyHtml,
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        public ?string $messageId = null,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = $this->fromAddress ?: config('mail.from.address');
        $fromName = $this->fromName ?: config('mail.from.name');

        return new Envelope(
            from: filled($fromAddress) ? new Address((string) $fromAddress, (string) ($fromName ?: '')) : null,
            subject: $this->emailSubject,
            to: $this->toAddresses,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->bodyHtml,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
        );
    }
}
