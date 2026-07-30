<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Services\External;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Laravel cache-реализация идемпотентности внешнего экспорта.
 */
final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Атомарно принимает operationId внешнего экспорта.
     */
    public function accept(string $operationId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($operationId), true, now()->addSeconds($this->ttlSeconds()));
    }

    /**
     * Снимает отметку принятого operationId после ошибки запуска.
     */
    public function forgetAccepted(string $operationId): void
    {
        $this->cache->store()->forget($this->acceptedKey($operationId));
    }

    private function acceptedKey(string $operationId): string
    {
        return sprintf((string) config('applicability.export.external.cache.keys.accepted'), $operationId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.export.external.cache.ttl_seconds');
    }
}
