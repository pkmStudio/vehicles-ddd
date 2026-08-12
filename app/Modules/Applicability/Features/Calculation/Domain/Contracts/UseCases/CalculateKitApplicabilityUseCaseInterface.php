<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

interface CalculateKitApplicabilityUseCaseInterface
{
    /**
     * Пересчитывает применяемость активных комплектов.
     *
     * Шаги:
     * 1. Создает operation id, если caller не передал внешний id.
     * 2. Читает активные kits с optional фильтром по kit id и chunk size.
     * 3. Рассчитывает каждый kit и синхронизирует рассчитанные targets.
     * 4. Собирает aggregate result, публикует факт завершения и возвращает DTO результата.
     */
    public function execute(?int $kitId = null, int $chunk = 1000, ?string $operationId = null): KitApplicabilityCalculationResultDTO;
}
