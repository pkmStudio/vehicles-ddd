<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperLengthExtractorInterface
{
    /**
     * Извлекает расчетные длины дворников по позиции комплекта.
     *
     * Шаги:
     * 1. Находит wiper-номенклатуры, релевантные front/back/universal позиции.
     * 2. Читает длины из details выбранных номенклатур.
     * 3. Возвращает DTO с размерами и количеством щеток в расчете.
     */
    public function extract(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO;
}
