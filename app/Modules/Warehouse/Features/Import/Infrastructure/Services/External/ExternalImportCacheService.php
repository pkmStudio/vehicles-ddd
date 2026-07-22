<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Services\External;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache-реализация идемпотентности и отложенной очистки файла внешних импортов.
 */
final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    /**
     * Принимает runId только один раз в пределах cache TTL.
     */
    public function accept(string $runId): bool
    {
        return Cache::add(
            key: $this->acceptedKey($runId),
            value: true,
            ttl: $this->ttlSeconds(),
        );
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
            value: ['disk' => $request->disk, 'path' => $request->path],
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

        if (! is_array($cleanup) || ! isset($cleanup['disk'], $cleanup['path'])) {
            return null;
        }

        return new ExternalImportFileCleanupDTO(
            disk: $cleanup['disk'],
            path: $cleanup['path'],
        );
    }

    /**
     * Собирает cache-ключ идемпотентности внешнего импорта.
     */
    private function acceptedKey(string $runId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.import.external.cache.keys.accepted',
            ),
            $runId,
        );
    }

    /**
     * Собирает cache-ключ отложенной очистки исходного файла.
     */
    private function cleanupKey(string $runId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.import.external.cache.keys.cleanup',
            ),
            $runId,
        );
    }

    /**
     * Возвращает TTL технических cache-записей внешнего импорта.
     */
    private function ttlSeconds(): int
    {
        return (int) config(
            key: 'warehouse.import.external.cache.ttl_seconds',
        );
    }
}
