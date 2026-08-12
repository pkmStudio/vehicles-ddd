<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Cache;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache-адаптер идемпотентности внешнего запуска экспорта.
 */
final readonly class LaravelExportRunCacheService implements ExportRunCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для идемпотентного запуска экспорта.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add($this->acceptedCacheKey($operationId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    /**
     * Снимает отметку принятого operationId после сбоя экспорта.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->acceptedCacheKey($operationId));
    }

    /**
     * Получить cache-ключ принятого внешнего запроса на экспорт.
     */
    private function acceptedCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.export.external.cache.keys.accepted'), $operationId);
    }

    /**
     * TTL технических cache-записей внешнего экспорта в секундах.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles.export.external.cache.ttl_seconds');
    }
}
