<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\Brand;

use App\Warehouse\Catalog\Domain\ModelData\BrandData;

/**
 * Доменный факт создания Warehouse-бренда.
 */
final readonly class BrandCreated
{
    /**
     * Хранит контекст операции и созданный бренд.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public BrandData $brand,
    ) {}
}
