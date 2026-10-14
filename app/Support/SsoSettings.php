<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

class SsoSettings
{
    public const KEY = 'sso';

    /** @var list<string> */
    public const PROVIDERS = ['google', 'azure'];

    /**
     * @return array{google_enabled: bool, azure_enabled: bool, allowed_domains: list<string>}
     */
    public static function defaults(): array
    {
        return [
            'google_enabled' => false,
            'azure_enabled' => false,
            'allowed_domains' => [],
        ];
    }

    /**
     * @return array{google_enabled: bool, azure_enabled: bool, allowed_domains: list<string>}
     */
    public static function get(): array
    {
        $stored = Setting::getValue(self::KEY);
        $merged = is_array($stored)
            ? array_merge(self::defaults(), $stored)
            : self::defaults();

        $merged['allowed_domains'] = self::normalizeDomains($merged['allowed_domains'] ?? []);

        return $merged;
    }

    /**
     * @param  list<string>  $allowedDomains
     */
    public static function put(bool $googleEnabled, bool $azureEnabled, array $allowedDomains): void
    {
        Setting::putValue(self::KEY, [
            'google_enabled' => $googleEnabled,
            'azure_enabled' => $azureEnabled,
            'allowed_domains' => self::normalizeDomains($allowedDomains),
        ]);
    }

    public static function isProviderEnabled(string $provider): bool
    {
        self::assertProvider($provider);

        $settings = self::get();

        return match ($provider) {
            'google' => (bool) $settings['google_enabled'],
            'azure' => (bool) $settings['azure_enabled'],
        };
    }

    public static function isConfigured(string $provider): bool
    {
        self::assertProvider($provider);

        $config = config('services.'.$provider);

        if (! is_array($config)) {
            return false;
        }

        $hasCore = filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);

        if ($provider === 'azure') {
            return $hasCore && filled($config['tenant'] ?? null);
        }

        return $hasCore;
    }

    public static function isVisible(string $provider): bool
    {
        return self::isProviderEnabled($provider)
            && self::isConfigured($provider)
            && self::allowedDomains() !== [];
    }

    /**
     * @return list<string>
     */
    public static function allowedDomains(): array
    {
        return self::get()['allowed_domains'];
    }

    public static function isDomainAllowed(string $email): bool
    {
        $domains = self::allowedDomains();

        if ($domains === []) {
            return false;
        }

        $normalizedEmail = strtolower(trim($email));

        foreach ($domains as $domain) {
            if (str_ends_with($normalizedEmail, '@'.$domain)) {
                return true;
            }
        }

        return false;
    }

    public static function isAvailable(): bool
    {
        foreach (self::PROVIDERS as $provider) {
            if (self::isVisible($provider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    public static function visibilityForLogin(): array
    {
        return [
            'google' => self::isVisible('google'),
            'azure' => self::isVisible('azure'),
        ];
    }

    /**
     * @return array{google: bool, azure: bool}
     */
    public static function credentialsStatus(): array
    {
        return [
            'google' => self::isConfigured('google'),
            'azure' => self::isConfigured('azure'),
        ];
    }

    /**
     * @param  list<string>|string  $domains
     * @return list<string>
     */
    public static function normalizeDomains(array|string $domains): array
    {
        if (is_string($domains)) {
            return self::parseDomains($domains);
        }

        return collect($domains)
            ->filter(fn ($domain) => is_string($domain))
            ->map(fn (string $domain) => strtolower(ltrim(trim($domain), '@')))
            ->filter(fn (string $domain) => $domain !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function parseDomains(string $raw): array
    {
        return self::normalizeDomains(preg_split('/\R/', $raw) ?: []);
    }

    private static function assertProvider(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException('Unknown SSO provider: '.$provider);
        }
    }
}
