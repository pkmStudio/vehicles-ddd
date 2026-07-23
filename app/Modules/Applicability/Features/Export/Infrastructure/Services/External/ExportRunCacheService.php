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
     * Атомарно принимает runId внешнего экспорта.
     */
    public function accept(string $runId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($runId), true, now()->addSeconds($this->ttlSeconds()));
    }

    /**
     * Снимает отметку принятого runId после ошибки запуска.
     */
    public function forgetAccepted(string $runId): void
    {
        $this->cache->store()->forget($this->acceptedKey($runId));
    }

    private function acceptedKey(string $runId): string
    {
        return sprintf((string) config('applicability.export.external.cache.keys.accepted'), $runId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.export.external.cache.ttl_seconds');
    }
}
