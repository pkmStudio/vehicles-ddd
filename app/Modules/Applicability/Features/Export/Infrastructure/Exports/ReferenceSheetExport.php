<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class ReferenceSheetExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Создает sheet adapter для справочных значений export-файла.
     *
     * Шаги:
     * 1. Принимает headings, уже подготовленные application service-ом.
     * 2. Принимает строки как Collection или массив для простых справочников.
     * 3. Сохраняет название листа с дефолтом `Справочники`.
     *
     * @param  array<int, string>  $headings
     * @param  Collection<int, array<int, mixed>>|array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private array $headings,
        private Collection|array $rows,
        private string $title = 'Справочники',
    ) {}

    /**
     * Возвращает название справочного листа workbook.
     *
     * Шаги:
     * 1. Берет title, переданный при создании sheet adapter-а.
     * 2. Возвращает его в Laravel Excel hook `WithTitle`.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Возвращает справочные строки в формате Laravel Collection.
     *
     * Шаги:
     * 1. Если rows уже переданы как Collection, возвращает их без копирования.
     * 2. Если rows переданы массивом, оборачивает их в Support Collection.
     */
    public function collection(): Collection
    {
        return $this->rows instanceof Collection ? $this->rows : collect($this->rows);
    }

    /**
     * Возвращает заголовки справочного листа.
     *
     * Шаги:
     * 1. Использует headings, переданные при создании sheet adapter-а.
     * 2. Возвращает их в Laravel Excel hook `WithHeadings`.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
