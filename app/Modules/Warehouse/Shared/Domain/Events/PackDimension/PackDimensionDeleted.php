<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\PackDimension;

/**
 * Доменный факт удаления упаковочного размера Warehouse.
 */
final readonly class PackDimensionDeleted
{
    /**
     * Хранит контекст операции и id удалённой упаковки.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $packDimensionId,
        public string $name,
    ) {}
}
