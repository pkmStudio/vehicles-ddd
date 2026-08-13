<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку командного импорта модификаций в DTO.
 */
final readonly class ModificationCommandRowMapper
{
    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при чтении идентификаторов и атрибутов модификации.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO модификации из строки командного импорта.
     *
     * Шаги:
     * 1) Прочитать ms_id, mod_id, годы выпуска, описание и характеристики из фиксированных колонок.
     * 2) Нормализовать числовые поля и текстовые признаки через общий нормализатор.
     * 3) Вернуть DTO для построчного сервиса сохранения модификации.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): ModificationCommandRowDTO
    {
        return new ModificationCommandRowDTO(
            msId: $this->formatter->nullableInt($row[0] ?? null, 'ms_id'),
            modId: $this->formatter->nullableInt($row[1] ?? null, 'mod_id'),
            yearFrom: $this->formatter->nullableInt($row[2] ?? null, 'year_from'),
            yearTo: $this->formatter->nullableInt($row[3] ?? null, 'year_to'),
            description: $this->formatter->nullableString($row[4] ?? null),
            powerPs: $this->formatter->nullableInt($row[5] ?? null, 'power_ps'),
            powerKw: $this->formatter->nullableInt($row[6] ?? null, 'power_kw'),
            engineType: $this->formatter->nullableString($row[7] ?? null),
            gearType: $this->formatter->nullableString($row[8] ?? null),
            driveType: $this->formatter->nullableString($row[9] ?? null),
            brakeSystemType: $this->formatter->nullableString($row[10] ?? null),
            numberOfCylinders: $this->formatter->nullableInt($row[11] ?? null, 'number_of_cylinders'),
            capacityLt: $this->formatter->nullableFloat($row[12] ?? null, 'capacity_lt'),
            type: $this->formatter->nullableString($row[13] ?? null),
        );
    }
}
