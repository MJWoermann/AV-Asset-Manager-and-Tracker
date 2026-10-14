<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

class EmailIntegrationSettings
{
    public const KEY = 'email_integration';

    /** @var list<string> */
    public const DRIVERS = ['gmail', 'outlook', 'smtp'];

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     from_name: string|null,
     *     from_address: string|null,
     *     outlook_mailbox: string|null,
     *     inbound_folder: string,
     *     poll_interval_minutes: int,
     *     imap_host: string|null,
     *     imap_port: int|null,
     *     imap_encryption: string|null,
     *     imap_username: string|null,
     * }
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'driver' => 'smtp',
            'from_name' => null,
            'from_address' => null,
            'outlook_mailbox' => null,
            'inbound_folder' => 'INBOX',
            'poll_interval_minutes' => 5,
            'imap_host' => null,
            'imap_port' => null,
            'imap_encryption' => 'ssl',
            'imap_username' => null,
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     from_name: string|null,
     *     from_address: string|null,
     *     outlook_mailbox: string|null,
     *     inbound_folder: string,
     *     poll_interval_minutes: int,
     *     imap_host: string|null,
     *     imap_port: int|null,
     *     imap_encryption: string|null,
     *     imap_username: string|null,
     * }
     */
    public static function get(): array
    {
        $stored = Setting::getValue(self::KEY);
        $merged = is_array($stored)
            ? array_merge(self::defaults(), $stored)
            : self::defaults();

        $driver = (string) ($merged['driver'] ?? 'smtp');
        if (! in_array($driver, self::DRIVERS, true)) {
            $driver = 'smtp';
        }
        $merged['driver'] = $driver;
        $merged['enabled'] = (bool) ($merged['enabled'] ?? false);
        $merged['poll_interval_minutes'] = max(1, (int) ($merged['poll_interval_minutes'] ?? 5));
        $merged['inbound_folder'] = filled($merged['inbound_folder'] ?? null)
            ? (string) $merged['inbound_folder']
            : 'INBOX';

        if ($driver === 'gmail') {
            $merged = array_merge($merged, self::gmailImapPresets());
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function put(array $input): void
    {
        $driver = (string) ($input['driver'] ?? 'smtp');
        if (! in_array($driver, self::DRIVERS, true)) {
            throw new InvalidArgumentException('Unknown email driver: '.$driver);
        }

        $payload = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'driver' => $driver,
            'from_name' => self::nullableString($input['from_name'] ?? null),
            'from_address' => self::nullableString($input['from_address'] ?? null),
            'outlook_mailbox' => self::nullableString($input['outlook_mailbox'] ?? null),
            'inbound_folder' => self::nullableString($input['inbound_folder'] ?? null) ?? 'INBOX',
            'poll_interval_minutes' => max(1, (int) ($input['poll_interval_minutes'] ?? 5)),
            'imap_host' => self::nullableString($input['imap_host'] ?? null),
            'imap_port' => isset($input['imap_port']) && $input['imap_port'] !== ''
                ? (int) $input['imap_port']
                : null,
            'imap_encryption' => self::nullableString($input['imap_encryption'] ?? null),
            'imap_username' => self::nullableString($input['imap_username'] ?? null),
        ];

        if ($driver === 'gmail') {
            $payload = array_merge($payload, self::gmailImapPresets());
        }

        Setting::putValue(self::KEY, $payload);
    }

    public static function isEnabled(): bool
    {
        return self::get()['enabled'] === true;
    }

    /**
     * @return array{imap_host: string, imap_port: int, imap_encryption: string}
     */
    public static function gmailImapPresets(): array
    {
        return [
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
        ];
    }

    /**
     * @return array{host: string, port: int, encryption: string}
     */
    public static function gmailSmtpPresets(): array
    {
        return [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
        ];
    }

    /**
     * @return array{mail: bool, graph: bool}
     */
    public static function credentialsStatus(): array
    {
        $mailConfigured = filled(config('mail.from.address'))
            && (
                filled(config('mail.mailers.smtp.username'))
                || filled(config('mail.mailers.smtp.password'))
                || config('mail.default') === 'log'
                || config('mail.default') === 'array'
            );

        $graph = filled(config('services.microsoft_graph.client_id'))
            && filled(config('services.microsoft_graph.client_secret'))
            && filled(config('services.microsoft_graph.tenant_id'));

        return [
            'mail' => $mailConfigured,
            'graph' => $graph,
        ];
    }

    protected static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
