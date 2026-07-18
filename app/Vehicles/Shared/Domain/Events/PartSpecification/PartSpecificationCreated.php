<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Events\PartSpecification;

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
        public array $specification,
    ) {}
}
