<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services;

interface WarehouseCatalogCascadeDeleteServiceInterface
{
    public function deleteNomenclaturesByBrandId(int $brandId): void;

    public function deleteKitsByPackDimensionId(int $packDimensionId): void;
}
