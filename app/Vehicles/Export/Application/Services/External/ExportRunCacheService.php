<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\External;

use App\Vehicles\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    public function accept(string $runId): bool
    {
        return Cache::add($this->acceptedCacheKey($runId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedCacheKey($runId));
    }

    /**
     * Получить cache-ключ принятого внешнего запроса на экспорт.
     */
    private function acceptedCacheKey(string $runId): string
    {
        return sprintf((string) config('vehicles.export.external.cache.keys.accepted'), $runId);
    }

    /**
     * TTL технических cache-записей внешнего экспорта в секундах.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles.export.external.cache.ttl_seconds', 86400);
    }
}
