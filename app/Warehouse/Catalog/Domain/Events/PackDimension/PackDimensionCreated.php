<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\PackDimension;

use App\Warehouse\Catalog\Domain\ModelData\PackDimensionData;

/**
 * Доменный факт создания упаковочного размера Warehouse.
 */
final readonly class PackDimensionCreated
{
    /**
     * Хранит контекст операции и созданную упаковку.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public PackDimensionData $packDimension,
    ) {}
}
