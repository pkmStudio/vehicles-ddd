<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\PartSpecificationData;

/**
 * Строка листа свечей зажигания после EngineSparkPlugSpecificationRowExpander: двигатель +
 * его спецификация (или null, если спецификаций нет).
 */
final readonly class PartSpecificationExportRowDTO
{
    /**
     * @param  EngineData  $entity  двигатель основной строки export sheet
     * @param  PartSpecificationData|null  $specification  specification свечей или null для пустой строки двигателя
     */
    public function __construct(
        public EngineData $entity,
        public ?PartSpecificationData $specification = null,
    ) {}
}
