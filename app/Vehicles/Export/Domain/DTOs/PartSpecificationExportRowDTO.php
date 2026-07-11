<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\DTOs;

use App\Vehicles\Export\Domain\ModelData\EngineData;
use App\Vehicles\Export\Domain\ModelData\PartSpecificationData;

/**
 * Строка листа свечей зажигания после PartSpecificationRowExpander: двигатель +
 * его спецификация (или null, если спецификаций нет).
 */
final readonly class PartSpecificationExportRowDTO
{
    public function __construct(
        public EngineData $entity,
        public ?PartSpecificationData $specification = null,
    ) {}
}
