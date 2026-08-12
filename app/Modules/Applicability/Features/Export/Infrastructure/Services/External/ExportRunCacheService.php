<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Services\External;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Laravel cache-реализация идемпотентности внешнего экспорта.
 */
final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    /**
     * Получает Laravel cache factory для idempotency markers export workflow.
     *
     * Шаги:
     * 1. Сохраняет cache factory, чтобы выбирать default store в runtime.
     * 2. Оставляет key/ttl lookup в отдельных helpers.
     */
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Атомарно принимает operationId внешнего экспорта.
     *
     * Шаги:
     * 1. Строит cache key принятого export operation id.
     * 2. Пытается записать marker через atomic `add`.
     * 3. Возвращает `false`, если такой marker уже существует.
     */
    public function accept(string $operationId): bool
    {
        return $this->cache->store()->add($this->acceptedKey($operationId), true, now()->addSeconds($this->ttlSeconds()));
    }

    /**
     * Снимает отметку принятого operationId после ошибки запуска.
     *
     * Шаги:
     * 1. Строит cache key принятого export operation id.
     * 2. Удаляет marker, чтобы повтор broker-сообщения мог снова запустить workflow.
     */
    public function forgetAccepted(string $operationId): void
    {
        $this->cache->store()->forget($this->acceptedKey($operationId));
    }

    /**
     * Формирует cache key для принятого export operation id.
     *
     * Шаги:
     * 1. Берет key template из config `applicability.export.external`.
     * 2. Подставляет operation id в template.
     */
    private function acceptedKey(string $operationId): string
    {
        return sprintf((string) config('applicability.export.external.cache.keys.accepted'), $operationId);
    }

    /**
     * Возвращает TTL idempotency marker-а export workflow.
     *
     * Шаги:
     * 1. Читает ttl в секундах из config `applicability.export.external`.
     * 2. Приводит значение к integer для Laravel cache expiration.
     */
    private function ttlSeconds(): int
    {
        return (int) config('applicability.export.external.cache.ttl_seconds');
    }
}
