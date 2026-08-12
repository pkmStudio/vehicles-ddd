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
     *
     * Шаги:
     * 1) Принять repository наборов как источник данных для экспорта.
     * 2) Принять mapper строки как единую точку сборки Excel-значений.
     * 3) Сохранить зависимости для дальнейших вызовов сервиса.
     */
    public function __construct(
        private KitRepositoryInterface $kits,
        private KitExportRowInterface $row,
    ) {}

    /**
     * Возвращает наборы, подготовленные Repository для Excel-адаптера.
     *
     * Шаги:
     * 1) Получить фильтры и сортировку из Excel-адаптера.
     * 2) Передать параметры в read-порт наборов.
     * 3) Вернуть коллекцию KitData без дополнительного преобразования.
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
     * Шаги:
     * 1) Делегировать построение заголовков row-сервису.
     * 2) Вернуть порядок колонок, который будет использовать mapRow().
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
     * Шаги:
     * 1) Получить KitData из Excel mapping callback.
     * 2) Передать снимок набора row-сервису.
     * 3) Вернуть массив значений в порядке заголовков.
     *
     * @return array<int, mixed>
     */
    public function mapRow(KitData $row): array
    {
        return $this->row->getData($row);
    }
}
