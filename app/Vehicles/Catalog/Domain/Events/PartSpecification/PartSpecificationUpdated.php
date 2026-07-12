<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\PartSpecification;

use App\Vehicles\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Фиксирует доменный факт обновления спецификации детали.
 */
final readonly class PartSpecificationUpdated
{
    /**
     * Инициализирует immutable-снимок события обновления спеки.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public PartSpecificationData $specification,
    ) {}
}
