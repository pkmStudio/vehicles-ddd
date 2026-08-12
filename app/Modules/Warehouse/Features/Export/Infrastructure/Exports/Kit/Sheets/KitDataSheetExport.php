<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit\Sheets;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист данных Warehouse-наборов.
 */
final readonly class KitDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Получает сервис подготовки строк и параметры отбора наборов.
     *
     * Шаги:
     * 1) Принять application-сервис экспорта наборов.
     * 2) Принять опциональные фильтры и сортировку из factory.
     * 3) Сохранить параметры до вызова Laravel Excel collection().
     */
    public function __construct(
        private KitExportServiceInterface $exportService,
        private ?KitExportFiltersDTO $filters = null,
        private ?KitExportSortDTO $sort = null,
    ) {}

    /**
     * Возвращает имя листа с наборами.
     *
     * Шаги:
     * 1) Использовать человекочитаемое русское название листа.
     * 2) Вернуть строку для Laravel Excel WithTitle.
     */
    public function title(): string
    {
        return 'Наборы';
    }

    /**
     * Загружает коллекцию наборов для Excel.
     *
     * Шаги:
     * 1) Подставить пустые DTO фильтра и сортировки, если параметры не переданы.
     * 2) Запросить строки у application-сервиса.
     * 3) Вернуть коллекцию Laravel Excel без eager-преобразования в массив.
     */
    public function collection(): Collection
    {
        $filters = $this->filters ?? new KitExportFiltersDTO;
        $sort = $this->sort ?? new KitExportSortDTO;

        return $this->exportService->getRows(
            filters: $filters,
            sort: $sort,
        );
    }

    /**
     * Преобразует одну строку набора для Excel.
     *
     * Шаги:
     * 1) Получить row от Laravel Excel.
     * 2) Делегировать mapping application-сервису.
     * 3) Вернуть плоский массив значений листа.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapRow($row);
    }

    /**
     * Возвращает заголовки листа наборов.
     *
     * Шаги:
     * 1) Запросить заголовки у application-сервиса.
     * 2) Вернуть их в Laravel Excel WithHeadings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings();
    }
}
