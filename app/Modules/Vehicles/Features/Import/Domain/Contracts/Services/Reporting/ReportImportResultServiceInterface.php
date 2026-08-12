<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting;

interface ReportImportResultServiceInterface
{
    /**
     * Сформировать и отправить итоговый import report.
     *
     * Шаги:
     * 1) Получить накопленные failures по cache key.
     * 2) Сформировать notification payload с итоговым статусом.
     * 3) Опубликовать результат и очистить временное состояние.
     */
    public function report(int $userId, string $cacheKey, ?string $operationId = null): void;
}
