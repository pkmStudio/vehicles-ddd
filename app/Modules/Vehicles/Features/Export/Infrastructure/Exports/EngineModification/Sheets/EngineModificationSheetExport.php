<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\EngineModification\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\EngineModificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet adapter связей модификаций и двигателей.
 */
final readonly class EngineModificationSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует read repository связей.
     */
    public function __construct(
        private EngineModificationRepositoryInterface $links,
    ) {}

    /**
     * Вернуть название листа связей.
     */
    public function title(): string
    {
        return 'Связи модификаций и двигателей';
    }

    /**
     * Вернуть строки связей.
     */
    public function collection(): Collection
    {
        return $this->links->all();
    }

    /**
     * Вернуть headings файла связей.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['mod_id', 'eng_id', 'type'];
    }

    /**
     * Преобразовать DTO связи в Excel row.
     *
     * @return array<int, string|int>
     */
    public function map($row): array
    {
        /** @var EngineModificationExportRowDTO $row */
        return [$row->modId, $row->engId, $row->type];
    }
}
