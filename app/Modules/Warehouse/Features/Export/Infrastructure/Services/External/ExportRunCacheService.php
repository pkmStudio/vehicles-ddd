<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Services\External;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache-реализация флага принятого внешнего запроса Warehouse-экспорта по operationId.
 */
final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    /**
     * Атомарно принимает новый operationId и отклоняет повтор того же запуска.
     *
     * Шаги:
     * 1) Собрать cache-ключ accepted-флага для operationId.
     * 2) Получить TTL технической записи из конфига.
     * 3) Выполнить Cache::add, чтобы запись появилась только один раз.
     * 4) Вернуть результат атомарного добавления.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add(
            key: $this->acceptedCacheKey($operationId),
            value: true,
            ttl: now()->addSeconds($this->cacheTtlSeconds()),
        );
    }

    /**
     * Снимает флаг принятого operationId, чтобы брокер мог повторить неуспешный запуск.
     *
     * Шаги:
     * 1) Собрать cache-ключ accepted-флага для operationId.
     * 2) Удалить запись из cache.
     * 3) Оставить повторную доставку брокера способной заново принять запуск.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget(
            key: $this->acceptedCacheKey($operationId),
        );
    }

    /**
     * Собирает cache-ключ принятого внешнего запроса по шаблону из конфига.
     */
    private function acceptedCacheKey(string $operationId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.export.external.cache.keys.accepted',
            ),
            $operationId,
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
