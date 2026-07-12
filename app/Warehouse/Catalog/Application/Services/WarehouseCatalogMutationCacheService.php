<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\Services;

use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Управляет cache-идемпотентностью входящих мутаций Warehouse-каталога.
 */
final readonly class WarehouseCatalogMutationCacheService implements WarehouseCatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для обработки.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add($this->key($operationId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку operationId после технического сбоя.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->key($operationId));
    }

    /**
     * Собирает cache-ключ идемпотентности по operationId.
     */
    private function key(string $operationId): string
    {
        return sprintf(
            (string) config('warehouse.catalog.mutations.cache.keys.accepted', 'warehouse_catalog_mutation_accepted_%s'),
            $operationId,
        );
    }

    /**
     * Возвращает TTL cache-ключа идемпотентности в секундах.
     */
    private function ttlSeconds(): int
    {
        return max(1, (int) config('warehouse.catalog.mutations.cache.ttl_seconds', 86400));
    }
}
