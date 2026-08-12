<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperApplicabilityServiceInterface
{
    /**
     * Рассчитывает применяемость комплекта дворников к автомобилям.
     *
     * Шаги:
     * 1. Извлекает позицию, длины и adapter-наборы из Warehouse kit.
     * 2. Находит подходящие vehicle wiper specifications по позиции и размерам.
     * 3. Фильтрует найденные автомобили по совместимости adapter-ов.
     * 4. Возвращает result DTO с target ids и алгоритмом расчета.
     */
    public function calculate(KitData $kit): KitApplicabilityKitResultDTO;
}
