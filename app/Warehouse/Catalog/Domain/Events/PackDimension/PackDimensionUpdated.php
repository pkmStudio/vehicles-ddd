<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\PackDimension;

use App\Warehouse\Catalog\Domain\ModelData\PackDimensionData;

/**
 * Доменный факт обновления упаковочного размера Warehouse.
 */
final readonly class PackDimensionUpdated
{
    /**
     * Хранит контекст операции и обновлённую упаковку.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public PackDimensionData $packDimension,
    ) {}
}
