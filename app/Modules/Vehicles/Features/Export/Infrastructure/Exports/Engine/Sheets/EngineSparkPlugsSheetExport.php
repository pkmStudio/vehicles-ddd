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
 * Laravel Excel sheet adapter листа спецификаций свечей зажигания.
 */
final readonly class EngineSparkPlugsSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует export service листа свечей.
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
     * 1) Вернуть фиксированное имя листа свечей зажигания.
     */
    public function title(): string
    {
        return 'Свечи зажигания';
    }

    /**
     * Возвращает строки листа свечей.
     *
     * Шаги:
     * 1) Делегировать выборку rows export service-у.
     */
    public function collection(): Collection
    {
        return $this->exportService->getSparkPlugRows();
    }

    /**
     * Преобразует row object в Excel row.
     *
     * Шаги:
     * 1) Передать row в export service mapper листа свечей.
     *
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapSparkPlugRow($row);
    }

    /**
     * Возвращает headings листа свечей.
     *
     * Шаги:
     * 1) Делегировать headings export service-у.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getSparkPlugHeadings();
    }
}
