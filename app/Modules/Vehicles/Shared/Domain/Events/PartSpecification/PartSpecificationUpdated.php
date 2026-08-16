<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\PartSpecification;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\PartSpecificationEventPayloadDTO;

/**
 * Фиксирует доменный факт обновления спецификации детали.
 */
final readonly class PartSpecificationUpdated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public PartSpecificationEventPayloadDTO $specification,
    ) {}
}
