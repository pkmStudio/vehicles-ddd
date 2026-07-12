<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Services\External;

use App\Warehouse\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Warehouse\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Warehouse\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;

/**
 * Идемпотентность и отложенная очистка файла внешних запусков Warehouse-импорта — через cache,
 * не БД (см. ARCHITECTURE.md, аналогичный паттерн у Vehicles/Import).
 */
final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    /**
     * Принимает runId только один раз в пределах cache TTL — атомарный Cache::add.
     */
    public function accept(string $runId): bool
    {
        return Cache::add($this->acceptedKey($runId), true, $this->ttlSeconds());
    }

    /**
     * Снимает флаг принятого runId после неуспешного запуска.
     */
    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedKey($runId));
    }

    /**
     * Запоминает disk+path исходного файла, чтобы удалить его после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        Cache::put(
            key: $this->cleanupKey($request->runId),
            value: new ExternalImportFileCleanupDTO(disk: $request->disk, path: $request->path),
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Забирает и удаляет из cache запомненное задание на очистку файла для runId, если оно есть.
     */
    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO
    {
        $key = $this->cleanupKey($runId);
        $cleanup = Cache::get($key);
        Cache::forget($key);

        return $cleanup instanceof ExternalImportFileCleanupDTO ? $cleanup : null;
    }

    private function acceptedKey(string $runId): string
    {
        return sprintf((string) config('warehouse.import.external.cache.keys.accepted'), $runId);
    }

    private function cleanupKey(string $runId): string
    {
        return sprintf((string) config('warehouse.import.external.cache.keys.cleanup'), $runId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('warehouse.import.external.cache.ttl_seconds', 86400);
    }
}
