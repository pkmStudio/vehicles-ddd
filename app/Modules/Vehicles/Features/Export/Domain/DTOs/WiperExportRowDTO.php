<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;

/**
 * Строка листа дворников после WiperRowExpander: ТС + спецификации по сторонам
 * (front/back), собранные обратно из раздельных по стороне записей.
 */
final readonly class WiperExportRowDTO
{
    /**
     * @param  VehicleData  $vehicle  автомобиль основной строки export sheet
     * @param  PartSpecificationData|null  $frontSpec  specification переднего дворника
     * @param  PartSpecificationData|null  $backSpec  specification заднего дворника
     */
    public function __construct(
        public VehicleData $vehicle,
        public ?PartSpecificationData $frontSpec = null,
        public ?PartSpecificationData $backSpec = null,
    ) {}
}
