<?php

namespace App\Contracts;

interface MailTransport
{
    /**
     * @param  list<string>  $to
     * @return array{provider_message_id: string|null, conversation_id: string|null}
     */
    public function send(array $to, string $subject, string $bodyHtml, ?string $fromAddress = null, ?string $fromName = null): array;

    /**
     * @return list<array{
     *     id: string,
     *     subject: string|null,
     *     from: string|null,
     *     body: string,
     *     snippet: string|null,
     *     in_reply_to: string|null,
     *     references: list<string>,
     *     conversation_id: string|null,
     *     headers: array<string, mixed>
     * }>
     */
    public function fetchUnread(int $limit = 25): array;

    public function markProcessed(string $messageId): void;
}
