<?php

namespace App\Support;

/**
 * Validates same-app relative return URLs to prevent open redirects.
 */
class SafeReturnUrl
{
    public const MAX_LENGTH = 2048;

    public static function sanitize(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || strlen($url) > self::MAX_LENGTH) {
            return null;
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        if (str_contains($url, '://') || str_contains($url, '\\')) {
            return null;
        }

        return $url;
    }
}
