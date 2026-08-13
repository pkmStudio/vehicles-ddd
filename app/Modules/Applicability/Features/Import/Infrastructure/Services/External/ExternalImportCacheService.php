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
    /**
     * Получает Laravel cache factory для idempotency и cleanup state import workflow.
     *
     * Шаги:
     * 1. Сохраняет cache factory, чтобы выбирать default store в runtime.
     * 2. Оставляет key/ttl lookup в отдельных helpers.
     */
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Атомарно принимает operationId внешнего импорта.
     *
     * Шаги:
     * 1. Строит cache key принятого import operation id.
     * 2. Пытается записать marker через atomic `add`.
     * 3. Возвращает `false`, если такой marker уже существует.
     */
    public function accept(string $operationId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($operationId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку принятого operationId после ошибки запуска.
     *
     * Шаги:
     * 1. Строит cache key принятого import operation id.
     * 2. Удаляет marker, чтобы повтор broker-сообщения мог снова запустить workflow.
     */
    public function forgetAccepted(string $operationId): void
    {
        $this->cache->store()->forget($this->acceptedKey($operationId));
    }

    /**
     * Запоминает исходный файл, который надо удалить после завершения импорта.
     *
     * Шаги:
     * 1. Строит cleanup key по operation id.
     * 2. Сохраняет disk и path исходного файла.
     * 3. Использует тот же TTL, что и idempotency marker workflow.
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
     *
     * Шаги:
     * 1. Строит cleanup key по operation id.
     * 2. Читает сохраненный payload и сразу удаляет key.
     * 3. Возвращает `null`, если payload отсутствует или поврежден.
     * 4. Возвращает cleanup DTO с disk/path для storage listener-а.
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

    /**
     * Формирует cache key для принятого import operation id.
     *
     * Шаги:
     * 1. Берет key template из config `applicability.import.external`.
     * 2. Подставляет operation id в template.
     */
    private function acceptedKey(string $operationId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.accepted'), $operationId);
    }

    /**
     * Формирует cache key cleanup metadata для import operation id.
     *
     * Шаги:
     * 1. Берет cleanup key template из config `applicability.import.external`.
     * 2. Подставляет operation id в template.
     */
    private function cleanupKey(string $operationId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.cleanup'), $operationId);
    }

    /**
     * Возвращает TTL transient state внешнего import workflow.
     *
     * Шаги:
     * 1. Читает ttl в секундах из config `applicability.import.external`.
     * 2. Приводит значение к integer для cache operations.
     */
    private function ttlSeconds(): int
    {
        return (int) config('applicability.import.external.cache.ttl_seconds');
    }
}
