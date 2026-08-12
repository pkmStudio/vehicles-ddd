<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleKitApplicabilityDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Получает service основного листа применяемости.
     *
     * Шаги:
     * 1. Сохраняет service, который читает строки и описывает Excel mapping.
     * 2. Оставляет sheet adapter без бизнес-правил применяемости.
     */
    public function __construct(
        private VehicleKitApplicabilityExportServiceInterface $service,
    ) {}

    /**
     * Возвращает название основного листа workbook.
     *
     * Шаги:
     * 1. Фиксирует пользовательское имя листа для данных применяемости.
     * 2. Возвращает его в Laravel Excel hook `WithTitle`.
     */
    public function title(): string
    {
        return 'Применяемость';
    }

    /**
     * Возвращает строки, которые Laravel Excel будет проходить при записи листа.
     *
     * Шаги:
     * 1. Запрашивает коллекцию DTO у application service.
     * 2. Передает коллекцию в Laravel Excel без дополнительной трансформации.
     */
    public function collection(): Collection
    {
        return $this->service->getRows();
    }

    /**
     * Преобразует одну DTO-строку в массив ячеек Excel.
     *
     * Шаги:
     * 1. Получает текущую строку из Laravel Excel iteration.
     * 2. Делегирует порядок и состав ячеек application service.
     */
    public function map($row): array
    {
        return $this->service->mapRow($row);
    }

    /**
     * Возвращает заголовки основного листа применяемости.
     *
     * Шаги:
     * 1. Берет headings из application service, где задан contract порядка колонок.
     * 2. Возвращает их в Laravel Excel hook `WithHeadings`.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->service->getHeadings();
    }
}
