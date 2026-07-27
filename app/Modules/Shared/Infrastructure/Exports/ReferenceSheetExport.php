<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Exports;

use App\Modules\Shared\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class ReferenceSheetExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * @param  array<int, string>  $headings
     * @param  Collection<int, array<int, mixed>>|array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private array $headings,
        private Collection|array $rows,
        private string $title = 'Справочники',
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function collection(): Collection
    {
        return $this->rows instanceof Collection ? $this->rows : collect($this->rows);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
