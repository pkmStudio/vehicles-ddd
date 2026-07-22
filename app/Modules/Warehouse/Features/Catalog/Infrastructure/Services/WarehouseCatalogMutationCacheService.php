<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Services;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache-реализация идемпотентности входящих мутаций Warehouse-каталога.
 */
final readonly class WarehouseCatalogMutationCacheService implements WarehouseCatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для обработки.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add(
            key: $this->key($operationId),
            value: true,
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Снимает отметку operationId после технического сбоя.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget(
            key: $this->key($operationId),
        );
    }

    /**
     * Собирает cache-ключ идемпотентности по operationId.
     */
    private function key(string $operationId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.catalog.mutations.cache.keys.accepted',
            ),
            $operationId,
        );
    }

    /**
     * Возвращает TTL cache-ключа идемпотентности в секундах.
     */
    private function ttlSeconds(): int
    {
        return max(
            1,
            (int) config(
                key: 'warehouse.catalog.mutations.cache.ttl_seconds',
            ),
        );
    }
}
