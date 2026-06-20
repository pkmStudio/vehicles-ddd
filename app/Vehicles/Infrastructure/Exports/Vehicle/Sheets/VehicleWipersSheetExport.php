<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle\Sheets;

use App\Vehicles\Application\Export\Services\VehicleExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист «дворники»: дворники хранятся по одной записи на сторону (front/back), а в excel
 * нужен старый объединённый формат. Expander собирает строки {frontSpec, backSpec},
 * map() склеивает стороны обратно в {front, back} через доменный сервис.
 */
final readonly class VehicleWipersSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private VehicleExportService $exportService,
        private bool $isAllow = false,
    ) {
    }

    public function title(): string
    {
        return 'Дворники';
    }

    public function collection(): Collection
    {
        return $this->exportService->getWiperRows($this->isAllow);
    }

    /**
     * @throws \Exception
     */
    public function map($row): array
    {
        return $this->exportService->mapWiperRow($row);
    }

    public function headings(): array
    {
        return $this->exportService->getWiperHeadings();
    }
}
