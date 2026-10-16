<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportApiState
{
    private static function key(int $userId): string
    {
        return 'api-import:'.$userId;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(int $userId): array
    {
        $state = Cache::get(self::key($userId), []);

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function merge(int $userId, array $data): void
    {
        Cache::put(self::key($userId), array_merge(self::get($userId), $data), now()->addHours(2));
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::key($userId));
    }
}
