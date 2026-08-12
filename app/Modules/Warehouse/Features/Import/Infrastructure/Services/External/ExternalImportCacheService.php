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
     * Принимает operationId только один раз в пределах cache TTL.
     * Шаги:
     * 1) Собрать cache key идемпотентности по operationId.
     * 2) Попытаться атомарно добавить флаг через Cache::add().
     * 3) Использовать TTL внешнего import workflow.
     * 4) Вернуть true только для первого принятого operationId.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add(
            key: $this->acceptedKey($operationId),
            value: true,
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Снимает флаг принятого operationId после неуспешного запуска.
     * Шаги:
     * 1) Собрать accepted cache key по operationId.
     * 2) Удалить флаг, чтобы broker retry мог повторить запуск.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->acceptedKey($operationId));
    }

    /**
     * Запоминает disk+path исходного файла, чтобы удалить его после завершения импорта.
     * Шаги:
     * 1) Собрать cleanup cache key по operationId request-а.
     * 2) Сохранить только disk и path исходного файла.
     * 3) Использовать тот же TTL, что и для accepted guard.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        Cache::put(
            key: $this->cleanupKey($request->operationId),
            value: ['disk' => $request->disk, 'path' => $request->path],
            ttl: $this->ttlSeconds(),
        );
    }

    /**
     * Забирает и удаляет из cache запомненное задание на очистку файла для operationId, если оно есть.
     * Шаги:
     * 1) Собрать cleanup cache key по operationId.
     * 2) Прочитать значение из cache.
     * 3) Сразу удалить cache entry, чтобы очистка была одноразовой.
     * 4) Вернуть null для отсутствующего или неполного payload.
     * 5) Собрать ExternalImportFileCleanupDTO из disk/path.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO
    {
        $key = $this->cleanupKey($operationId);
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
     * Шаги:
     * 1) Взять key pattern из warehouse.import.external config.
     * 2) Подставить operationId в configured pattern.
     */
    private function acceptedKey(string $operationId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.import.external.cache.keys.accepted',
            ),
            $operationId,
        );
    }

    /**
     * Собирает cache-ключ отложенной очистки исходного файла.
     * Шаги:
     * 1) Взять cleanup key pattern из warehouse.import.external config.
     * 2) Подставить operationId в configured pattern.
     */
    private function cleanupKey(string $operationId): string
    {
        return sprintf(
            (string) config(
                key: 'warehouse.import.external.cache.keys.cleanup',
            ),
            $operationId,
        );
    }

    /**
     * Возвращает TTL технических cache-записей внешнего импорта.
     * Шаги:
     * 1) Прочитать warehouse.import.external.cache.ttl_seconds из config.
     * 2) Привести значение к int для Laravel cache API.
     */
    private function ttlSeconds(): int
    {
        return (int) config(
            key: 'warehouse.import.external.cache.ttl_seconds',
        );
    }
}
