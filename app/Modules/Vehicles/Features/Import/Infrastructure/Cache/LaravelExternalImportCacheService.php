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
    /**
     * Атомарно принять внешний import request по operationId.
     *
     * Шаги:
     * 1) Собрать cache-key принятого operationId.
     * 2) Записать marker через atomic Cache::add с TTL.
     * 3) Вернуть true только для первого принятого запроса.
     */
    public function accept(ExternalImportFileRequestDTO $request): bool
    {
        return Cache::add($this->acceptedCacheKey($request->operationId), true, now()->addSeconds($this->cacheTtlSeconds()));
    }

    /**
     * Снять marker принятого operationId после ошибки запуска.
     *
     * Шаги:
     * 1) Собрать cache-key принятого operationId.
     * 2) Удалить marker, чтобы повторная доставка могла перезапустить импорт.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->acceptedCacheKey($operationId));
    }

    /**
     * Сохранить инструкцию отложенной очистки исходного файла.
     *
     * Шаги:
     * 1) Собрать cleanup DTO из disk/path import request.
     * 2) Записать serialized cleanup payload в cache по operationId.
     * 3) Ограничить хранение общим TTL внешнего import flow.
     */
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

    /**
     * Забрать и удалить инструкцию очистки исходного файла.
     *
     * Шаги:
     * 1) Выполнить atomic pull cleanup payload из cache.
     * 2) Вернуть null, если payload отсутствует или поврежден.
     * 3) Восстановить ExternalImportFileCleanupDTO из сохраненных scalar fields.
     */
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
     *
     * Шаги:
     * 1) Прочитать шаблон accepted key из Vehicles import config.
     * 2) Подставить operationId во внешний idempotency key.
     */
    private function acceptedCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.accepted'), $operationId);
    }

    /**
     * Получить cache-ключ инструкции очистки внешнего файла.
     *
     * Шаги:
     * 1) Прочитать шаблон cleanup key из Vehicles import config.
     * 2) Подставить operationId в ключ cleanup instruction.
     */
    private function cleanupCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.cleanup'), $operationId);
    }

    /**
     * TTL технических cache-записей внешнего импорта в секундах.
     *
     * Шаги:
     * 1) Прочитать TTL из Vehicles import config.
     * 2) Вернуть fallback 86400 секунд, если настройка отсутствует.
     */
    private function cacheTtlSeconds(): int
    {
        return (int) config('vehicles.import.external.cache.ttl_seconds', 86400);
    }
}
