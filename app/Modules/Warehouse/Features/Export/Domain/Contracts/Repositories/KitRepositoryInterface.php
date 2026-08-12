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
     * Шаги:
     * 1) Принять фильтры и сортировку экспортного запроса.
     * 2) Применить их к источнику Warehouse-наборов.
     * 3) Вернуть KitData со связями, нужными для Excel-строк.
     *
     * @return Collection<int, KitData>
     */
    public function all(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection;
}
