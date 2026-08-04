<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;

/**
 * Читает Warehouse-бренды для Catalog-мутаций.
 */
final readonly class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Возвращает бренд по typed lookup-критерию или null.
     */
    public function find(BrandLookupDTO $lookup): ?BrandData
    {
        $query = Brand::query();

        if ($lookup->id !== null) {
            return BrandData::optional($query->find($lookup->id));
        }

        $brand = $query
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $lookup->name))])
            ->first();

        return BrandData::optional($brand);
    }
}
