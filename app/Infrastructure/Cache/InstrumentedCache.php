<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;

class InstrumentedCache
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $hitKey  = 'cache:hits:' . $key;
        $missKey = 'cache:misses:' . $key;
        $miss    = false;

        $value = Cache::remember($key, $ttl, function () use ($callback, $missKey, &$miss) {
            $miss = true;
            try {
                Cache::increment($missKey);
            } catch (\Throwable) {
                // Stats tracking must never interrupt the main cache flow
            }
            return $callback();
        });

        if (!$miss) {
            try {
                Cache::increment($hitKey);
            } catch (\Throwable) {
                // Stats tracking must never interrupt the main cache flow
            }
        }

        return $value;
    }

    public static function getStats(string $key): array
    {
        return [
            'hits'   => (int) Cache::get('cache:hits:' . $key, 0),
            'misses' => (int) Cache::get('cache:misses:' . $key, 0),
        ];
    }
}
