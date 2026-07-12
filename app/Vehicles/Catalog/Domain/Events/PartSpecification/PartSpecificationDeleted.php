<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\PartSpecification;

/**
 * Фиксирует доменный факт удаления спецификации детали.
 */
final readonly class PartSpecificationDeleted
{
    /**
     * Инициализирует immutable-снимок события удаления спеки.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $specificationId,
    ) {}
}
