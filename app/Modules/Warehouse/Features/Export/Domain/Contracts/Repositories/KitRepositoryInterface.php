<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\KitData;
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
