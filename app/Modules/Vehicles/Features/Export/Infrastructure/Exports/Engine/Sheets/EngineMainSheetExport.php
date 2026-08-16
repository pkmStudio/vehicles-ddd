<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel sheet adapter основного листа двигателей.
 */
final readonly class EngineMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует export service листа двигателей.
     *
     * Шаги:
     * 1) Сохранить service, который поставляет rows/headings/mapping.
     */
    public function __construct(
        private EngineExportServiceInterface $exportService,
    ) {}

    /**
     * Возвращает название sheet.
     *
     * Шаги:
     * 1) Вернуть фиксированное имя листа двигателей.
     */
    public function title(): string
    {
        return 'Двигатели';
    }

    /**
     * Возвращает строки основного листа двигателей.
     *
     * Шаги:
     * 1) Делегировать выборку rows export service-у.
     */
    public function collection(): Collection
    {
        return $this->exportService->getMainRows();
    }

    /**
     * Преобразует row object в Excel row.
     *
     * Шаги:
     * 1) Передать row в export service mapper основного листа.
     *
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return $this->exportService->mapMainRow($row);
    }

    /**
     * Возвращает headings основного листа двигателей.
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
