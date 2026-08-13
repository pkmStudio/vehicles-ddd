<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

interface CalculationFailureReporterInterface
{
    /**
     * Сохраняет отчет по ошибкам расчета, если в результате есть failures.
     *
     * Шаги:
     * 1. Проверяет aggregate result на наличие ошибок.
     * 2. Формирует файл отчета в configured storage.
     * 3. Возвращает путь к отчету или `null`, если ошибок нет.
     */
    public function store(KitApplicabilityCalculationResultDTO $result): ?string;
}
