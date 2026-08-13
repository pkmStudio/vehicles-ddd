<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation;

/**
 * Итог одного запуска расчёта применяемости.
 *
 * @param  array<int, int>  $affectedKitIds
 * @param  array<int, string>  $errors
 */
final readonly class KitApplicabilityCalculationResultDTO
{
    /**
     * Создает агрегированный итог запуска расчета.
     */
    public function __construct(
        public string $operationId,
        public int $processedKits = 0,
        public int $calculatedKits = 0,
        public int $skippedKits = 0,
        public int $failedKits = 0,
        public array $affectedKitIds = [],
        public array $errors = [],
    ) {}
}
