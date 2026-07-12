<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Exports\Nomenclature\Sheets;

use App\Warehouse\Export\Domain\Contracts\Services\NomenclatureExportServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист данных Warehouse-номенклатуры выбранного типа.
 */
final readonly class NomenclatureDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * Получает сервис экспорта номенклатуры и id выбранного типа.
     */
    public function __construct(
        private NomenclatureExportServiceInterface $exportService,
        private int $typeId,
    ) {}

    /**
     * Возвращает название листа, ограниченное лимитом Excel в 31 символ.
     */
    public function title(): string
    {
        return mb_substr($this->exportService->title($this->typeId), 0, 31);
    }

    /**
     * Возвращает строки номенклатуры выбранного типа.
     */
    public function collection(): Collection
    {
        return $this->exportService->getRows($this->typeId);
    }

    /**
     * Мапит одну строку номенклатуры в плоский массив значений Excel.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapRow($row);
    }

    /**
     * Возвращает базовые и detail-заголовки листа номенклатуры.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings($this->typeId);
    }
}
