<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\PartSpecification;

use App\Vehicles\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Фиксирует доменный факт создания спецификации детали.
 */
final readonly class PartSpecificationCreated
{
    /**
     * Инициализирует immutable-снимок события создания спеки.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public PartSpecificationData $specification,
    ) {}
}
