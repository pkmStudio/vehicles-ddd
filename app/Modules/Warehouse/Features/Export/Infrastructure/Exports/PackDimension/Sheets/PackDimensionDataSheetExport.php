<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\PackDimension\Sheets;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\PackDimensionExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист данных упаковочных размеров Warehouse.
 */
final readonly class PackDimensionDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Получает сервис подготовки строк упаковочных размеров.
     *
     * Шаги:
     * 1) Принять application-сервис экспорта упаковок.
     * 2) Сохранить сервис для collection(), map() и headings().
     */
    public function __construct(
        private PackDimensionExportServiceInterface $exportService,
    ) {}

    /**
     * Возвращает имя листа упаковок.
     *
     * Шаги:
     * 1) Использовать короткое русское имя листа.
     * 2) Вернуть строку для Laravel Excel WithTitle.
     */
    public function title(): string
    {
        return 'Упаковки';
    }

    /**
     * Загружает строки упаковочных размеров.
     *
     * Шаги:
     * 1) Запросить данные через application-сервис.
     * 2) Вернуть коллекцию PackDimensionData для Laravel Excel.
     */
    public function collection(): Collection
    {
        return $this->exportService->getRows();
    }

    /**
     * Преобразует упаковочный размер в Excel-строку.
     *
     * Шаги:
     * 1) Получить row от Laravel Excel.
     * 2) Передать row в application-сервис.
     * 3) Вернуть плоский массив значений.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapRow($row);
    }

    /**
     * Возвращает заголовки листа упаковок.
     *
     * Шаги:
     * 1) Запросить заголовки у application-сервиса.
     * 2) Вернуть порядок колонок для Laravel Excel.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings();
    }
}
