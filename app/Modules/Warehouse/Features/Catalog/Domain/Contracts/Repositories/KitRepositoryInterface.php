<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-наборов для Catalog-мутаций.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по typed lookup-критерию или null.
     */
    public function find(KitLookupDTO $lookup): ?KitData;

    /**
     * Возвращает ids наборов упаковочного размера.
     *
     * @return Collection<int, int>
     */
    public function findIdsByPackDimensionId(int $packDimensionId): Collection;
}
