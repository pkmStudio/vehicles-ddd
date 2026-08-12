<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel sheet adapter основного листа автомобилей.
 */
final readonly class VehicleMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует export service и фильтр разрешенных автомобилей.
     *
     * Шаги:
     * 1) Сохранить service, который поставляет rows/headings/mapping.
     * 2) Сохранить флаг `isAllow` для фильтра rows.
     */
    public function __construct(
        private VehicleExportServiceInterface $exportService,
        private bool $isAllow = false,
    ) {}

    /**
     * Возвращает название sheet.
     *
     * Шаги:
     * 1) Вернуть фиксированное имя основного листа автомобилей.
     */
    public function title(): string
    {
        return 'Основная информация';
    }

    /**
     * Возвращает строки основного листа автомобилей.
     *
     * Шаги:
     * 1) Делегировать выборку rows export service-у с флагом `isAllow`.
     */
    public function collection(): Collection
    {
        return $this->exportService->getMainRows($this->isAllow);
    }

    /**
     * Преобразует row object в Excel row.
     *
     * Шаги:
     * 1) Передать row в export service mapper основного листа.
     *
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapMainRow($row);
    }

    /**
     * Возвращает headings основного листа автомобилей.
     *
     * Шаги:
     * 1) Делегировать headings export service-у.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getMainHeadings();
    }
}
