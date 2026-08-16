<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\PackDimension;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\PackDimensionEventPayloadDTO;

/**
 * Доменный факт создания упаковочного размера Warehouse.
 */
final readonly class PackDimensionCreated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public PackDimensionEventPayloadDTO $packDimension,
    ) {}
}
