<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\Services;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows\KitExportRowInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Координирует чтение наборов и построение строк Excel для Warehouse-экспорта.
 */
final readonly class KitExportService implements KitExportServiceInterface
{
    /**
     * Получает порты чтения наборов и построения Excel-строки.
     */
    public function __construct(
        private KitRepositoryInterface $kits,
        private KitExportRowInterface $row,
    ) {}

    /**
     * Возвращает наборы, подготовленные Repository для Excel-адаптера.
     *
     * @return Collection<int, KitData>
     */
    public function getRows(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection
    {
        return $this->kits->all(
            filters: $filters,
            sort: $sort,
        );
    }

    /**
     * Возвращает заголовки листа наборов.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return $this->row->getHeadings();
    }

    /**
     * Преобразует один набор в плоскую строку Excel.
     *
     * @return array<int, mixed>
     */
    public function mapRow(KitData $row): array
    {
        return $this->row->getData($row);
    }
}
