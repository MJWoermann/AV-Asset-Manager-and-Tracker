<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

class EmailIntegrationSettings
{
    public const KEY = 'email_integration';

    /** @var list<string> */
    public const DRIVERS = ['gmail', 'outlook', 'smtp'];

    public const DEFAULT_DIGEST_DAYS_AHEAD = 30;

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
     *     digest_warranty_enabled: bool,
     *     digest_test_tag_enabled: bool,
     *     digest_low_stock_enabled: bool,
     *     digest_days_ahead: int,
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
            'digest_warranty_enabled' => false,
            'digest_test_tag_enabled' => false,
            'digest_low_stock_enabled' => false,
            'digest_days_ahead' => self::DEFAULT_DIGEST_DAYS_AHEAD,
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
     *     digest_warranty_enabled: bool,
     *     digest_test_tag_enabled: bool,
     *     digest_low_stock_enabled: bool,
     *     digest_days_ahead: int,
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
        $merged['digest_warranty_enabled'] = (bool) ($merged['digest_warranty_enabled'] ?? false);
        $merged['digest_test_tag_enabled'] = (bool) ($merged['digest_test_tag_enabled'] ?? false);
        $merged['digest_low_stock_enabled'] = (bool) ($merged['digest_low_stock_enabled'] ?? false);
        $merged['digest_days_ahead'] = max(1, (int) ($merged['digest_days_ahead'] ?? self::DEFAULT_DIGEST_DAYS_AHEAD));

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

        $previous = self::get();
        $enabled = (bool) ($input['enabled'] ?? false);

        $payload = [
            'enabled' => $enabled,
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

        // Digest options only apply when integration is enabled; otherwise preserve prior values.
        if ($enabled && array_key_exists('digest_warranty_enabled', $input)) {
            $payload['digest_warranty_enabled'] = (bool) $input['digest_warranty_enabled'];
            $payload['digest_test_tag_enabled'] = (bool) ($input['digest_test_tag_enabled'] ?? false);
            $payload['digest_low_stock_enabled'] = (bool) ($input['digest_low_stock_enabled'] ?? false);
            $payload['digest_days_ahead'] = max(
                1,
                (int) ($input['digest_days_ahead'] ?? self::DEFAULT_DIGEST_DAYS_AHEAD)
            );
        } else {
            $payload['digest_warranty_enabled'] = (bool) $previous['digest_warranty_enabled'];
            $payload['digest_test_tag_enabled'] = (bool) $previous['digest_test_tag_enabled'];
            $payload['digest_low_stock_enabled'] = (bool) $previous['digest_low_stock_enabled'];
            $payload['digest_days_ahead'] = max(1, (int) $previous['digest_days_ahead']);
        }

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
     * Digests UI and scheduled sends require integration on and driver credentials present.
     */
    public static function digestsAvailable(): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $settings = self::get();
        $credentials = self::credentialsStatus();

        if ($settings['driver'] === 'outlook') {
            return $credentials['graph'];
        }

        return $credentials['mail'];
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
