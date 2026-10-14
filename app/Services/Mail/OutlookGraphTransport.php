<?php

namespace App\Services\Mail;

use App\Contracts\MailTransport;
use App\Support\EmailIntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OutlookGraphTransport implements MailTransport
{
    public function send(array $to, string $subject, string $bodyHtml, ?string $fromAddress = null, ?string $fromName = null): array
    {
        $mailbox = EmailIntegrationSettings::get()['outlook_mailbox']
            ?: $fromAddress
            ?: config('mail.from.address');

        if (! filled($mailbox)) {
            throw new RuntimeException('Outlook mailbox is not configured.');
        }

        $token = $this->accessToken();
        $message = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $bodyHtml,
                ],
                'toRecipients' => collect($to)->map(fn (string $email): array => [
                    'emailAddress' => ['address' => $email],
                ])->values()->all(),
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->graphUrl('/users/'.rawurlencode((string) $mailbox).'/sendMail'), $message);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph sendMail failed: '.$response->status().' '.$response->body());
        }

        return [
            'provider_message_id' => $response->header('request-id') ?: ('graph-'.Str::uuid()->toString()),
            'conversation_id' => null,
        ];
    }

    public function fetchUnread(int $limit = 25): array
    {
        $settings = EmailIntegrationSettings::get();
        $mailbox = $settings['outlook_mailbox'] ?: config('mail.from.address');
        if (! filled($mailbox)) {
            throw new RuntimeException('Outlook mailbox is not configured for inbound polling.');
        }

        $folder = $settings['inbound_folder'] ?: 'Inbox';
        $token = $this->accessToken();
        $path = '/users/'.rawurlencode((string) $mailbox)
            .'/mailFolders/'.rawurlencode($folder)
            .'/messages';

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->graphUrl($path), [
                '$top' => $limit,
                '$filter' => 'isRead eq false',
                '$select' => 'id,subject,bodyPreview,body,from,internetMessageId,conversationId,internetMessageHeaders',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph list messages failed: '.$response->status().' '.$response->body());
        }

        $items = $response->json('value') ?? [];
        $messages = [];

        foreach ($items as $item) {
            $headers = collect($item['internetMessageHeaders'] ?? [])
                ->mapWithKeys(fn (array $header): array => [
                    strtolower((string) ($header['name'] ?? '')) => (string) ($header['value'] ?? ''),
                ]);

            $bodyContent = (string) data_get($item, 'body.content', '');
            $contentType = strtolower((string) data_get($item, 'body.contentType', 'text'));
            if ($contentType === 'html') {
                $bodyContent = trim(html_entity_decode(strip_tags($bodyContent)));
            }

            $references = preg_split('/\s+/', (string) ($headers['references'] ?? '')) ?: [];

            $messages[] = [
                'id' => (string) ($item['internetMessageId'] ?? $item['id']),
                'subject' => $item['subject'] ?? null,
                'from' => data_get($item, 'from.emailAddress.address'),
                'body' => $bodyContent,
                'snippet' => $item['bodyPreview'] ?? Str::limit($bodyContent, 280),
                'in_reply_to' => $headers['in-reply-to'] ?? null,
                'references' => array_values(array_filter($references)),
                'conversation_id' => $item['conversationId'] ?? null,
                'headers' => [
                    'graph_id' => $item['id'] ?? null,
                    'internet_message_headers' => $item['internetMessageHeaders'] ?? [],
                ],
            ];
        }

        return $messages;
    }

    public function markProcessed(string $messageId): void
    {
        $settings = EmailIntegrationSettings::get();
        $mailbox = $settings['outlook_mailbox'] ?: config('mail.from.address');
        $graphId = null;

        // Prefer Graph id from headers when available; otherwise skip mark-read.
        if (str_starts_with($messageId, 'AA')) {
            $graphId = $messageId;
        }

        if (! filled($mailbox) || ! filled($graphId)) {
            return;
        }

        Http::withToken($this->accessToken())
            ->acceptJson()
            ->patch($this->graphUrl('/users/'.rawurlencode((string) $mailbox).'/messages/'.rawurlencode($graphId)), [
                'isRead' => true,
            ]);
    }

    protected function accessToken(): string
    {
        $tenant = config('services.microsoft_graph.tenant_id');
        $clientId = config('services.microsoft_graph.client_id');
        $clientSecret = config('services.microsoft_graph.client_secret');

        if (! filled($tenant) || ! filled($clientId) || ! filled($clientSecret)) {
            throw new RuntimeException('Microsoft Graph credentials are not configured.');
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        );

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            throw new RuntimeException('Unable to obtain Microsoft Graph access token.');
        }

        return (string) $response->json('access_token');
    }

    protected function graphUrl(string $path): string
    {
        return 'https://graph.microsoft.com/v1.0'.$path;
    }
}
