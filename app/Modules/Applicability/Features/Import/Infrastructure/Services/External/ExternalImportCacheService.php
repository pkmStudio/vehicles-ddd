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
     * Атомарно принимает operationId внешнего импорта.
     */
    public function accept(string $operationId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($operationId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку принятого operationId после ошибки запуска.
     */
    public function forgetAccepted(string $operationId): void
    {
        $this->cache->store()->forget($this->acceptedKey($operationId));
    }

    /**
     * Запоминает исходный файл, который надо удалить после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        $this->cache->store()->put(
            key: $this->cleanupKey($request->operationId),
            value: ['disk' => $request->disk, 'path' => $request->path],
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Забирает и удаляет сохраненный контекст очистки исходного файла.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO
    {
        $key = $this->cleanupKey($operationId);
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

    private function acceptedKey(string $operationId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.accepted'), $operationId);
    }

    private function cleanupKey(string $operationId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.cleanup'), $operationId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.import.external.cache.ttl_seconds');
    }
}
