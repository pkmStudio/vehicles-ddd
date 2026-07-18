<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Nomenclature\Sheets;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\NomenclatureExportServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист справочников для импорта/проверки Warehouse-номенклатуры выбранного типа.
 */
final readonly class NomenclatureReferenceSheetExport implements FromCollection, WithHeadings, WithTitle
{
    /**
     * Получает сервис экспорта номенклатуры и id выбранного типа.
     */
    public function __construct(
        private NomenclatureExportServiceInterface $exportService,
        private int $typeId,
    ) {}

    /**
     * Возвращает фиксированное название справочного листа.
     */
    public function title(): string
    {
        return 'Справочники';
    }

    /**
     * Возвращает строки справочников для выбранного типа.
     */
    public function collection(): Collection
    {
        return $this->exportService->getReferenceRows($this->typeId);
    }

    /**
     * Возвращает заголовки справочного листа.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getReferenceHeadings($this->typeId);
    }
}
