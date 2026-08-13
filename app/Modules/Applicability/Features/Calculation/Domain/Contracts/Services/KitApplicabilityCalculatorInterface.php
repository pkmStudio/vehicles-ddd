<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface KitApplicabilityCalculatorInterface
{
    /**
     * Рассчитывает применяемость одного Warehouse kit.
     *
     * Шаги:
     * 1. Проверяет template комплекта.
     * 2. Выбирает template-specific calculation service.
     * 3. Возвращает result DTO или `null`, если комплект нельзя рассчитать.
     */
    public function calculate(KitData $kit): ?KitApplicabilityKitResultDTO;
}
