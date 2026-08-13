<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperAdapterExtractorInterface
{
    /**
     * Извлекает adapter codes из номенклатур комплекта дворников.
     *
     * Шаги:
     * 1. Выбирает wiper-номенклатуры, релевантные позиции комплекта.
     * 2. Читает adapter fields из details каждой номенклатуры.
     * 3. Возвращает полный список adapters и adapters, которые нужно применить при сравнении.
     */
    public function extract(KitData $kit, WiperKitPositionEnum $position): WiperAdaptersDTO;
}
