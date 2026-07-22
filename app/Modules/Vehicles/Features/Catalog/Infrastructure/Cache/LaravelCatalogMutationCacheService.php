<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Cache;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache-адаптер идемпотентности входящих мутаций каталога.
 */
final readonly class LaravelCatalogMutationCacheService implements CatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для идемпотентной обработки.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add($this->key($operationId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку принятого operationId после сбоя обработки.
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
            (string) config('vehicles-catalog.mutations.cache.keys.accepted'),
            $operationId,
        );
    }

    /**
     * Возвращает TTL cache-ключа идемпотентности в секундах.
     */
    private function ttlSeconds(): int
    {
        return max(1, (int) config('vehicles-catalog.mutations.cache.ttl_seconds', 86400));
    }
}
