<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\External;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Управляет cache-идемпотентностью внешнего запуска экспорта.
 */
final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    /**
     * Атомарно принимает runId для идемпотентного запуска экспорта.
     */
    public function accept(string $runId): bool
    {
        return Cache::add($this->acceptedCacheKey($runId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    /**
     * Снимает отметку принятого runId после сбоя экспорта.
     */
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
