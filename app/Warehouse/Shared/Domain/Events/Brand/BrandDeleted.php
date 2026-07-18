<?php

declare(strict_types=1);

namespace App\Warehouse\Shared\Domain\Events\Brand;

/**
 * Доменный факт удаления Warehouse-бренда.
 */
final readonly class BrandDeleted
{
    /**
     * Хранит контекст операции и id удалённого бренда.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $brandId,
        public string $name,
    ) {}
}
