<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Repositories;

use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-наборов для Export-фичи.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает все наборы с загруженным составом.
     *
     * @return Collection<int, KitData>
     */
    public function all(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection;
}
