<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Modification\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet adapter каталога модификаций.
 */
final readonly class ModificationSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует read repository модификаций.
     *
     * Шаги:
     * 1) Сохранить repository, который возвращает typed snapshots.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
    ) {}

    /**
     * Вернуть название листа модификаций.
     */
    public function title(): string
    {
        return 'Модификации';
    }

    /**
     * Вернуть строки листа модификаций.
     *
     * Шаги:
     * 1) Делегировать чтение repository.
     */
    public function collection(): Collection
    {
        return $this->modifications->all();
    }

    /**
     * Вернуть headings листа модификаций.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ms_id',
            'mod_id',
            'localized_name',
            'year_from',
            'year_to',
            'capacity_lt',
            'engine_type',
            'power_ps',
            'power_kw',
            'drive_type',
            'gear_type',
            'brake_system_type',
            'number_of_cylinders',
            'description',
            'description_short',
            'type',
            'provider',
        ];
    }

    /**
     * Преобразовать typed modification snapshot в Excel row.
     *
     * Шаги:
     * 1) Прочитать поля в порядке headings.
     * 2) Enum-значения вывести как wire strings.
     *
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        /** @var ModificationData $row */
        return [
            $row->msId,
            $row->modId,
            $row->localizedName,
            $row->yearFrom,
            $row->yearTo,
            $row->capacityLt,
            $row->engineType?->value,
            $row->powerPs,
            $row->powerKw,
            $row->driveType?->value,
            $row->gearType?->value,
            $row->brakeSystemType?->value,
            $row->numberOfCylinders,
            $row->description,
            $row->descriptionShort,
            $row->type->value,
            $row->provider->value,
        ];
    }
}
