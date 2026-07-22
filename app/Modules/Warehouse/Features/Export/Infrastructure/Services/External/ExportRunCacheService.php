<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Services\External;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache-реализация флага принятого внешнего запроса Warehouse-экспорта по runId.
 */
final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    /**
     * Атомарно принимает новый runId и отклоняет повтор того же запуска.
     */
    public function accept(string $runId): bool
    {
        return Cache::add(
            key: $this->acceptedCacheKey($runId),
            value: true,
            ttl: now()->addSeconds($this->cacheTtlSeconds()),
        );
    }

    /**
     * Снимает флаг принятого runId, чтобы брокер мог повторить неуспешный запуск.
     */
    public function forgetAccepted(string $runId): void
    {
        Cache::forget(
            key: $this->acceptedCacheKey($runId),
        );
    }

    /**
     * Собирает cache-ключ принятого внешнего запроса по шаблону из конфига.
     */
    private function acceptedCacheKey(string $runId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.export.external.cache.keys.accepted',
            ),
            $runId,
        );
    }

    /**
     * Возвращает TTL технических cache-записей Warehouse-экспорта.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config(
            key: 'warehouse.export.external.cache.ttl_seconds',
        );
    }
}
