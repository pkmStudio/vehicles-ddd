<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

interface ExternalCalculationContextServiceInterface
{
    /**
     * Запоминает пользователя внешнего расчета по operation id.
     *
     * Шаги:
     * 1. Строит cache key по operation id.
     * 2. Сохраняет user id на configured TTL.
     */
    public function rememberUserId(string $operationId, int $userId): void;

    /**
     * Забирает пользователя внешнего расчета по operation id.
     *
     * Шаги:
     * 1. Строит cache key по operation id.
     * 2. Читает сохраненный user id.
     * 3. Удаляет cache entry и возвращает int value или `null`.
     */
    public function pullUserId(string $operationId): ?int;
}
