<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\DTOs;

use App\Vehicles\Export\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Export\Domain\ModelData\VehicleData;

/**
 * Строка листа дворников после WiperRowExpander: ТС + спецификации по сторонам
 * (front/back), собранные обратно из раздельных по стороне записей.
 */
final readonly class WiperExportRowDTO
{
    public function __construct(
        public VehicleData $vehicle,
        public ?PartSpecificationData $frontSpec = null,
        public ?PartSpecificationData $backSpec = null,
    ) {}
}
