<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class LaravelExternalCalculationContextService implements ExternalCalculationContextServiceInterface
{
    /**
     * Получает Laravel cache repository для временного external context.
     *
     * Шаги:
     * 1. Сохраняет cache repository.
     * 2. Использует configured keys и TTL в read/write методах.
     */
    public function __construct(
        private CacheRepository $cache,
    ) {}

    /**
     * Сохраняет user id внешнего расчета в cache.
     *
     * Шаги:
     * 1. Строит cache key по operation id.
     * 2. Вычисляет TTL из конфигурации с защитой от нулевого значения.
     * 3. Записывает user id во временный cache context.
     */
    public function rememberUserId(string $operationId, int $userId): void
    {
        $this->cache->put($this->userIdKey($operationId), $userId, $this->ttl());
    }

    /**
     * Забирает user id внешнего расчета из cache.
     *
     * Шаги:
     * 1. Строит cache key по operation id.
     * 2. Читает значение и сразу удаляет его из cache.
     * 3. Возвращает int user id или `null`, если значение отсутствует/некорректно.
     */
    public function pullUserId(string $operationId): ?int
    {
        $key = $this->userIdKey($operationId);
        $userId = $this->cache->get($key);
        $this->cache->forget($key);

        return is_numeric($userId) ? (int) $userId : null;
    }

    private function userIdKey(string $operationId): string
    {
        return sprintf(
            (string) config('applicability.calculation.external.cache.keys.user_id'),
            $operationId,
        );
    }

    private function ttl(): int
    {
        return max(1, (int) config('applicability.calculation.external.cache.ttl_seconds', 86400));
    }
}
