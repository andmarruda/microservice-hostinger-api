<?php

namespace App\Infrastructure\Quota;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HostingerQuotaTracker
{
    private const TTL = 172800; // 48 hours as specified by ADR-015

    private string $date;

    public function __construct()
    {
        $this->date = date('Y-m-d');
    }

    public function increment(string $resourceType = 'other'): void
    {
        // Global daily counter
        $globalKey = "hostinger:quota:{$this->date}";
        $globalCount = Cache::increment($globalKey);
        if ($globalCount === 1) {
            Cache::put($globalKey, 1, self::TTL);
        }

        // Per-resource-type counter (ADR-015: hostinger:api:calls:{date}:{resource_type})
        $resourceKey = "hostinger:quota:{$this->date}:{$resourceType}";
        $resourceCount = Cache::increment($resourceKey);
        if ($resourceCount === 1) {
            Cache::put($resourceKey, 1, self::TTL);
        }

        $warnAt    = $this->getWarningThreshold();
        $hardLimit = $this->getHardLimit();

        if ($globalCount >= $hardLimit) {
            Log::error('HostingerQuotaTracker: daily hard limit reached.', [
                'count'         => $globalCount,
                'hard_limit'    => $hardLimit,
                'date'          => $this->date,
                'resource_type' => $resourceType,
            ]);
        } elseif ($globalCount >= $warnAt) {
            Log::warning('HostingerQuotaTracker: approaching daily quota limit.', [
                'count'         => $globalCount,
                'warn_at'       => $warnAt,
                'date'          => $this->date,
                'resource_type' => $resourceType,
            ]);
        }
    }

    public function getToday(): int
    {
        return (int) Cache::get("hostinger:quota:{$this->date}", 0);
    }

    public function getTodayForResource(string $resourceType): int
    {
        return (int) Cache::get("hostinger:quota:{$this->date}:{$resourceType}", 0);
    }

    public function getTodayByResource(): array
    {
        $types = ['vps', 'billing', 'domains', 'dns', 'hosting', 'reach', 'orders', 'other'];
        $result = [];

        foreach ($types as $type) {
            $count = $this->getTodayForResource($type);
            if ($count > 0) {
                $result[$type] = $count;
            }
        }

        return $result;
    }

    public function isHardLimitReached(): bool
    {
        $hardLimit = $this->getHardLimit();

        // If no hard limit is set (0 or null), never block
        if ($hardLimit <= 0) {
            return false;
        }

        return $this->getToday() >= $hardLimit;
    }

    public function getWarningThreshold(): int
    {
        return (int) config('hostinger.quota.warn_at', 800);
    }

    public function getHardLimit(): int
    {
        return (int) config('hostinger.quota.hard_limit', 0);
    }
}
