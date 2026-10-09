<?php

namespace App\Support;

class GitVersion
{
    private static ?string $resolved = null;

    private static bool $resolvedSet = false;

    /**
     * Short commit SHA for the deployed codebase (or APP_VERSION override).
     */
    public static function short(): ?string
    {
        if (self::$resolvedSet) {
            return self::$resolved;
        }

        self::$resolvedSet = true;

        $configured = config('app.version');

        if (is_string($configured) && $configured !== '') {
            return self::$resolved = $configured;
        }

        return self::$resolved = self::fromGitRepository();
    }

    /**
     * @internal Used by tests to clear memoization between cases.
     */
    public static function flush(): void
    {
        self::$resolved = null;
        self::$resolvedSet = false;
    }

    private static function fromGitRepository(): ?string
    {
        $gitDir = base_path('.git');

        if (! is_dir($gitDir)) {
            return null;
        }

        $headPath = $gitDir.DIRECTORY_SEPARATOR.'HEAD';

        if (! is_file($headPath)) {
            return null;
        }

        $head = trim((string) file_get_contents($headPath));

        if ($head === '') {
            return null;
        }

        if (str_starts_with($head, 'ref:')) {
            $ref = trim(substr($head, 4));
            $refPath = $gitDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ref);

            if (is_file($refPath)) {
                $sha = trim((string) file_get_contents($refPath));

                return self::shortenSha($sha);
            }

            return self::fromPackedRefs($gitDir, $ref);
        }

        return self::shortenSha($head);
    }

    private static function fromPackedRefs(string $gitDir, string $ref): ?string
    {
        $packedRefs = $gitDir.DIRECTORY_SEPARATOR.'packed-refs';

        if (! is_file($packedRefs)) {
            return null;
        }

        $lines = file($packedRefs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || str_starts_with($line, '^')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 2);

            if ($parts === false || count($parts) !== 2) {
                continue;
            }

            if ($parts[1] === $ref) {
                return self::shortenSha($parts[0]);
            }
        }

        return null;
    }

    private static function shortenSha(string $sha): ?string
    {
        if (! preg_match('/^[0-9a-f]{7,40}$/i', $sha)) {
            return null;
        }

        return strtolower(substr($sha, 0, 7));
    }
}
