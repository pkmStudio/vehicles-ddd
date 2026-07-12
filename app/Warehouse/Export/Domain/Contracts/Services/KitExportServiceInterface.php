<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Services;

use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт подготовки строк и заголовков Excel-листа Warehouse-наборов.
 */
interface KitExportServiceInterface
{
    /**
     * Возвращает строки наборов для Excel-адаптера.
     *
     * @return Collection<int, KitData>
     */
    public function getRows(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection;

    /**
     * Возвращает заголовки листа наборов.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Преобразует набор в строку Excel.
     *
     * @return array<int, mixed>
     */
    public function mapRow(KitData $row): array;
}
