<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Cache;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache-адаптер состояния внешнего импорта: идемпотентность и отложенная очистка файла.
 */
final readonly class LaravelExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    public function accept(ExternalImportFileRequestDTO $request): bool
    {
        return Cache::add($this->acceptedCacheKey($request->operationId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->acceptedCacheKey($operationId));
    }

    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        $cleanup = new ExternalImportFileCleanupDTO(
            disk: $request->disk,
            path: $request->path,
        );

        Cache::put(
            $this->cleanupCacheKey($request->operationId),
            $cleanup->toArray(),
            now()->addSeconds($this->cacheTtlSeconds()),
        );
    }

    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO
    {
        $data = Cache::pull($this->cleanupCacheKey($operationId));

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
    private function acceptedCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.accepted'), $operationId);
    }

    /**
     * Получить cache-ключ инструкции очистки внешнего файла.
     */
    private function cleanupCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.cleanup'), $operationId);
    }

    /**
     * TTL технических cache-записей внешнего импорта в секундах.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles.import.external.cache.ttl_seconds', 86400);
    }
}
