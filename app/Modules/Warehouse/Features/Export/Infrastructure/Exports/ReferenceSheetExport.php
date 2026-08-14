<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Универсальный справочный лист Warehouse-экспорта.
 */
final readonly class ReferenceSheetExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * @param  array<int, string>  $headings
     * @param  Collection<int, array<int, mixed>>|array<int, array<int, mixed>>  $rows
     *
     * Шаги:
     * 1) Принять готовые заголовки справочного листа.
     * 2) Принять строки как Collection или массив.
     * 3) Сохранить название листа либо использовать значение по умолчанию.
     */
    public function __construct(
        private array $headings,
        private Collection|array $rows,
        private string $title = 'Справочники',
    ) {}

    /**
     * Возвращает имя справочного листа.
     *
     * Шаги:
     * 1) Взять title, переданный при создании листа.
     * 2) Вернуть строку для Laravel Excel WithTitle.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Возвращает строки справочника как Collection.
     *
     * Шаги:
     * 1) Проверить, пришли ли строки уже коллекцией.
     * 2) Если пришёл массив, обернуть его в Support Collection.
     * 3) Вернуть унифицированную коллекцию для Laravel Excel.
     */
    public function collection(): Collection
    {
        return $this->rows instanceof Collection ? $this->rows : collect($this->rows);
    }

    /**
     * Возвращает заголовки справочного листа.
     *
     * Шаги:
     * 1) Использовать заголовки, переданные в конструктор.
     * 2) Вернуть их без изменения порядка.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
