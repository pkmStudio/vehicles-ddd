<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Events\PartSpecification;

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
        public array $specification,
    ) {}
}
