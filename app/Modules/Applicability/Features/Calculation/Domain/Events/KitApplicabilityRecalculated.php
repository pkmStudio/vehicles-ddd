<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Events;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

/**
 * Фиксирует завершение запуска расчета применяемости наборов.
 */
final readonly class KitApplicabilityRecalculated
{
    /**
     * Инициализирует immutable-снимок результата расчета.
     */
    public function __construct(
        public string $operationId,
        public KitApplicabilityCalculationResultDTO $result,
    ) {}
}
