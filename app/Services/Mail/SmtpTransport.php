<?php

namespace App\Services\Mail;

use App\Contracts\MailTransport;
use App\Mail\NotificationTemplateMail;
use App\Support\EmailIntegrationSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use IMAP\Connection;
use RuntimeException;

class SmtpTransport implements MailTransport
{
    public function send(array $to, string $subject, string $bodyHtml, ?string $fromAddress = null, ?string $fromName = null): array
    {
        $messageId = '<'.Str::uuid()->toString().'@'.parse_url((string) config('app.url'), PHP_URL_HOST).'>';

        Mail::mailer($this->mailerName())->send(new NotificationTemplateMail(
            $to,
            $subject,
            $bodyHtml,
            $fromAddress,
            $fromName,
            $messageId,
        ));

        return [
            'provider_message_id' => $messageId,
            'conversation_id' => null,
        ];
    }

    public function fetchUnread(int $limit = 25): array
    {
        if (! function_exists('imap_open')) {
            throw new RuntimeException('PHP IMAP extension is required for inbound email with the SMTP/Gmail driver.');
        }

        $settings = EmailIntegrationSettings::get();
        $host = $settings['imap_host'] ?? null;
        $port = (int) ($settings['imap_port'] ?? 993);
        $encryption = $settings['imap_encryption'] ?? 'ssl';
        $username = $settings['imap_username'] ?: config('mail.mailers.smtp.username');
        $password = (string) config('mail.mailers.smtp.password');
        $folder = $settings['inbound_folder'] ?: 'INBOX';

        if (! filled($host) || ! filled($username) || $password === '') {
            throw new RuntimeException('IMAP host/username/password are not configured for inbound email.');
        }

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        }
        $flags .= '/novalidate-cert';

        $mailbox = sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);
        $connection = @imap_open($mailbox, (string) $username, $password);

        if ($connection === false) {
            throw new RuntimeException('Unable to open IMAP mailbox: '.(imap_last_error() ?: 'unknown error'));
        }

        try {
            $ids = imap_search($connection, 'UNSEEN') ?: [];
            $ids = array_slice($ids, 0, $limit);
            $messages = [];

            foreach ($ids as $id) {
                $overview = imap_fetch_overview($connection, (string) $id, 0)[0] ?? null;
                $header = imap_headerinfo($connection, (int) $id);
                $rawHeaders = imap_fetchheader($connection, (int) $id) ?: '';
                $body = $this->fetchPlainBody($connection, (int) $id);
                $messageId = isset($overview->message_id) ? trim((string) $overview->message_id) : null;
                $inReplyTo = isset($header->in_reply_to) ? trim((string) $header->in_reply_to) : null;
                $references = [];
                if (isset($header->references) && is_string($header->references)) {
                    $references = preg_split('/\s+/', trim($header->references)) ?: [];
                }

                $from = null;
                if (isset($header->from[0])) {
                    $mailboxPart = $header->from[0]->mailbox ?? '';
                    $hostPart = $header->from[0]->host ?? '';
                    $from = $mailboxPart !== '' && $hostPart !== ''
                        ? $mailboxPart.'@'.$hostPart
                        : null;
                }

                $messages[] = [
                    'id' => $messageId ?: ('imap-'.$id),
                    'subject' => isset($overview->subject) ? (string) $overview->subject : null,
                    'from' => $from,
                    'body' => $body,
                    'snippet' => Str::limit(strip_tags($body), 280),
                    'in_reply_to' => $inReplyTo,
                    'references' => array_values(array_filter($references)),
                    'conversation_id' => null,
                    'headers' => [
                        'raw' => Str::limit($rawHeaders, 4000),
                        'imap_uid' => $id,
                    ],
                ];
            }

            return $messages;
        } finally {
            imap_close($connection);
        }
    }

    public function markProcessed(string $messageId): void
    {
        // Messages are fetched with UNSEEN; reading marks them seen on most servers.
        // No-op for SMTP/IMAP when uid is unavailable after fetch.
    }

    protected function mailerName(): string
    {
        $settings = EmailIntegrationSettings::get();

        if ($settings['driver'] === 'gmail') {
            $presets = EmailIntegrationSettings::gmailSmtpPresets();
            config([
                'mail.mailers.smtp.host' => $presets['host'],
                'mail.mailers.smtp.port' => $presets['port'],
                'mail.mailers.smtp.encryption' => $presets['encryption'],
            ]);
        }

        return config('mail.default') === 'array' ? 'array' : 'smtp';
    }

    /**
     * @param  resource|Connection  $connection
     */
    protected function fetchPlainBody($connection, int $id): string
    {
        $structure = imap_fetchstructure($connection, $id);
        if (! $structure) {
            return (string) imap_body($connection, $id);
        }

        if (($structure->type ?? 0) === 0) {
            $body = (string) imap_body($connection, $id);
            $encoding = (int) ($structure->encoding ?? 0);

            return $this->decodeBody($body, $encoding);
        }

        if (($structure->type ?? 0) === 1 && ! empty($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $subtype = strtolower((string) ($part->subtype ?? ''));
                if (($part->type ?? 0) === 0 && ($subtype === 'plain' || $subtype === 'html')) {
                    $body = (string) imap_fetchbody($connection, $id, (string) ($index + 1));
                    $decoded = $this->decodeBody($body, (int) ($part->encoding ?? 0));
                    if ($subtype === 'html') {
                        return trim(html_entity_decode(strip_tags($decoded)));
                    }

                    return $decoded;
                }
            }
        }

        return trim(html_entity_decode(strip_tags((string) imap_body($connection, $id))));
    }

    protected function decodeBody(string $body, int $encoding): string
    {
        $decoded = match ($encoding) {
            3 => base64_decode($body, true) ?: $body,
            4 => quoted_printable_decode($body),
            default => $body,
        };

        return trim($decoded);
    }
}
