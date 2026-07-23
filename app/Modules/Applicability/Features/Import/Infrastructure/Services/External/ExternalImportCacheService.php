<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Services\External;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Laravel cache-реализация идемпотентности и отложенной очистки внешнего импорта.
 */
final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Атомарно принимает runId внешнего импорта.
     */
    public function accept(string $runId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($runId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку принятого runId после ошибки запуска.
     */
    public function forgetAccepted(string $runId): void
    {
        $this->cache->store()->forget($this->acceptedKey($runId));
    }

    /**
     * Запоминает исходный файл, который надо удалить после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        $this->cache->store()->put(
            key: $this->cleanupKey($request->runId),
            value: ['disk' => $request->disk, 'path' => $request->path],
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Забирает и удаляет сохраненный контекст очистки исходного файла.
     */
    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO
    {
        $key = $this->cleanupKey($runId);
        $cleanup = $this->cache->store()->get($key);
        $this->cache->store()->forget($key);

        if (! is_array($cleanup) || ! isset($cleanup['disk'], $cleanup['path'])) {
            return null;
        }

        return new ExternalImportFileCleanupDTO(
            disk: (string) $cleanup['disk'],
            path: (string) $cleanup['path'],
        );
    }

    private function acceptedKey(string $runId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.accepted'), $runId);
    }

    private function cleanupKey(string $runId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.cleanup'), $runId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.import.external.cache.ttl_seconds');
    }
}
