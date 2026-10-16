<?php

namespace App\Support;

class TrustedExternalDomains
{
    public const PREFERENCE_KEY = 'trusted_external_domains';

    public const MAX_HOSTS = 50;

    /**
     * @param  array<string, mixed>|null  $preferences
     * @return list<string>
     */
    public static function hosts(?array $preferences): array
    {
        $raw = $preferences[self::PREFERENCE_KEY] ?? null;

        if (! is_array($raw)) {
            return [];
        }

        return self::normalizeList($raw);
    }

    public static function hostFromUrl(string $url): ?string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return self::normalizeHost($host);
    }

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function isTrusted(?array $preferences, string $url): bool
    {
        $host = self::hostFromUrl($url);

        if ($host === null) {
            return false;
        }

        return in_array($host, self::hosts($preferences), true);
    }

    /**
     * Add a hostname parsed from a valid http(s) URL (or a bare hostname).
     *
     * @param  array<string, mixed>|null  $preferences
     * @return array<string, mixed>
     */
    public static function mergeAdd(?array $preferences, string $urlOrHost): array
    {
        $preferences = is_array($preferences) ? $preferences : [];
        $host = self::hostFromUrl($urlOrHost);

        if ($host === null) {
            $host = self::normalizeHost($urlOrHost);
        }

        if ($host === null) {
            return $preferences;
        }

        $hosts = self::hosts($preferences);

        if (! in_array($host, $hosts, true)) {
            $hosts[] = $host;
        }

        $preferences[self::PREFERENCE_KEY] = array_slice($hosts, 0, self::MAX_HOSTS);

        return $preferences;
    }

    /**
     * @param  array<string, mixed>|null  $preferences
     * @return array<string, mixed>
     */
    public static function mergeRemove(?array $preferences, string $host): array
    {
        $preferences = is_array($preferences) ? $preferences : [];
        $normalized = self::normalizeHost($host);

        if ($normalized === null) {
            return $preferences;
        }

        $hosts = array_values(array_filter(
            self::hosts($preferences),
            fn (string $existing): bool => $existing !== $normalized
        ));

        if ($hosts === []) {
            unset($preferences[self::PREFERENCE_KEY]);
        } else {
            $preferences[self::PREFERENCE_KEY] = $hosts;
        }

        return $preferences;
    }

    public static function normalizeHost(string $host): ?string
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return null;
        }

        // Strip brackets from IPv6 literals; store hostname only (no port).
        if (str_starts_with($host, '[') && str_contains($host, ']')) {
            $host = substr($host, 1, (int) strpos($host, ']') - 1);
        } elseif (str_contains($host, ':') && substr_count($host, ':') === 1) {
            [$name, $port] = explode(':', $host, 2);
            if (! ctype_digit($port)) {
                return null;
            }
            $host = $name;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        return $host;
    }

    /**
     * @param  array<mixed>  $raw
     * @return list<string>
     */
    protected static function normalizeList(array $raw): array
    {
        $hosts = [];

        foreach ($raw as $value) {
            if (! is_string($value)) {
                continue;
            }

            $host = self::normalizeHost($value);

            if ($host === null || in_array($host, $hosts, true)) {
                continue;
            }

            $hosts[] = $host;

            if (count($hosts) >= self::MAX_HOSTS) {
                break;
            }
        }

        return $hosts;
    }
}
