<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\Brand;

use App\Warehouse\Catalog\Domain\ModelData\BrandData;

/**
 * Доменный факт обновления Warehouse-бренда.
 */
final readonly class BrandUpdated
{
    /**
     * Хранит контекст операции и обновлённый бренд.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public BrandData $brand,
    ) {}
}
