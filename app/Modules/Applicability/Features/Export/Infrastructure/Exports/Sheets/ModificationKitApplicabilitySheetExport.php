<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\ModificationKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class ModificationKitApplicabilitySheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Создает лист XLSX применяемости к модификациям.
     *
     * @param  Collection<int, ModificationKitApplicabilityRowDTO>|null  $rows
     */
    public function __construct(
        private ModificationKitApplicabilityExportServiceInterface $service,
        private string $title,
        private ?Collection $rows = null,
    ) {}

    /**
     * Возвращает название листа, совместимое с импортом `kit_applicability`.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Возвращает строки листа; пустые справочные листы остаются импортируемыми.
     */
    public function collection(): Collection
    {
        return $this->rows ?? $this->service->getRows();
    }

    /**
     * Преобразует DTO строки в массив значений Excel.
     */
    public function map($row): array
    {
        return $this->service->mapRow($row);
    }

    /**
     * Возвращает заголовки листа в порядке импортного mapper-а.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->service->getHeadings();
    }
}
