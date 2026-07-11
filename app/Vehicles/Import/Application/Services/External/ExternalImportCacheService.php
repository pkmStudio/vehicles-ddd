<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\External;

use App\Vehicles\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Vehicles\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Vehicles\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Управляет cache-состоянием внешнего импорта: идемпотентность и отложенная очистка файла.
 */
final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    public function accept(ExternalImportFileRequestDTO $request): bool
    {
        if (Cache::add($this->acceptedCacheKey($request->runId), true, now()->addSeconds($this->cacheTtlSeconds()))) {
            return true;
        }

        Log::warning('External import file request has already been accepted', [
            'run_id' => $request->runId,
            'import_type' => $request->importType->value,
        ]);

        return false;
    }

    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedCacheKey($runId));
    }

    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        Cache::put(
            $this->cleanupCacheKey($request->runId),
            new ExternalImportFileCleanupDTO(
                disk: $request->disk,
                path: $request->path,
            )->toArray(),
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
        return sprintf((string) config('vehicles-import.external.cache.keys.accepted'), $runId);
    }

    /**
     * Получить cache-ключ инструкции очистки внешнего файла.
     */
    private function cleanupCacheKey(string $runId): string
    {
        return sprintf((string) config('vehicles-import.external.cache.keys.cleanup'), $runId);
    }

    /**
     * TTL технических cache-записей внешнего импорта в секундах.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles-import.external.cache.ttl_seconds', 86400);
    }
}
