<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;

/**
 * Порт чтения Warehouse-брендов для Catalog-мутаций.
 */
interface BrandRepositoryInterface
{
    /**
     * Возвращает бренд по typed lookup-критерию или null.
     */
    public function find(BrandLookupDTO $lookup): ?BrandData;
}
