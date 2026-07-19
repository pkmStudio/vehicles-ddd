<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\External;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;

/**
 * Управляет cache-состоянием внешнего импорта: идемпотентность и отложенная очистка файла.
 */
final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    public function accept(ExternalImportFileRequestDTO $request): bool
    {
        return Cache::add($this->acceptedCacheKey($request->runId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedCacheKey($runId));
    }

    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        $cleanup = new ExternalImportFileCleanupDTO(
            disk: $request->disk,
            path: $request->path,
        );

        Cache::put(
            $this->cleanupCacheKey($request->runId),
            $cleanup->toArray(),
            now()->addSeconds($this->cacheTtlSeconds()),
        );
    }

    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO
    {
        $data = Cache::pull($this->cleanupCacheKey($runId));

        if (! is_array($data)) {
            return null;
        }

        return new ExternalImportFileCleanupDTO(
            disk: (string) $data['disk'],
            path: (string) $data['path'],
        );
    }

    /**
     * Получить cache-ключ принятого внешнего запроса.
     */
    private function acceptedCacheKey(string $runId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.accepted'), $runId);
    }

    /**
     * Получить cache-ключ инструкции очистки внешнего файла.
     */
    private function cleanupCacheKey(string $runId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.cleanup'), $runId);
    }

    /**
     * TTL технических cache-записей внешнего импорта в секундах.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles.import.external.cache.ttl_seconds', 86400);
    }
}
