<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel sheet adapter для справочных значений export-файла.
 */
final readonly class ReferenceSheetExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует headings, rows и title справочного листа.
     *
     * Шаги:
     * 1) Сохранить заголовки reference columns.
     * 2) Сохранить rows как collection или array.
     * 3) Сохранить title листа или использовать стандартное имя.
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
     * Возвращает название sheet для Laravel Excel.
     *
     * Шаги:
     * 1) Вернуть title, переданный в constructor.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Возвращает rows справочного листа как Support Collection.
     *
     * Шаги:
     * 1) Если rows уже являются Collection — вернуть их без изменений.
     * 2) Иначе обернуть array rows в Support Collection.
     */
    public function collection(): Collection
    {
        return $this->rows instanceof Collection ? $this->rows : collect($this->rows);
    }

    /**
     * Возвращает заголовки справочного листа.
     *
     * Шаги:
     * 1) Вернуть headings, переданные в constructor.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
