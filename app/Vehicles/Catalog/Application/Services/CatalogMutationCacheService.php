<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Services;

use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

final readonly class CatalogMutationCacheService implements CatalogMutationCacheServiceInterface
{
    public function accept(string $operationId): bool
    {
        return Cache::add($this->key($operationId), true, $this->ttlSeconds());
    }

    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->key($operationId));
    }

    private function key(string $operationId): string
    {
        return sprintf(
            (string) config('vehicles-catalog.mutations.cache.keys.accepted'),
            $operationId,
        );
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config('vehicles-catalog.mutations.cache.ttl_seconds', 86400));
    }
}
