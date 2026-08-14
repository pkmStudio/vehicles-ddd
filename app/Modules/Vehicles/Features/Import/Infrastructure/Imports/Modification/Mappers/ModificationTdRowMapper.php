<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationTdRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку командного импорта модификаций в DTO.
 */
final readonly class ModificationTdRowMapper
{
    private const int MS_ID = 0;

    private const int MOD_ID = 1;

    private const int YEAR_FROM = 2;

    private const int YEAR_TO = 3;

    private const int DESCRIPTION = 4;

    private const int POWER_PS = 5;

    private const int POWER_KW = 6;

    private const int ENGINE_TYPE = 7;

    private const int GEAR_TYPE = 8;

    private const int DRIVE_TYPE = 9;

    private const int BRAKE_SYSTEM_TYPE = 10;

    private const int NUMBER_OF_CYLINDERS = 11;

    private const int CAPACITY_LT = 12;

    private const int TYPE = 13;

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
    public function map(array $row): ModificationTdRowDTO
    {
        return new ModificationTdRowDTO(
            msId: $this->formatter->requiredInt($row[self::MS_ID] ?? null, 'ms_id'),
            modId: $this->formatter->requiredInt($row[self::MOD_ID] ?? null, 'mod_id'),
            yearFrom: $this->formatter->requiredInt($row[self::YEAR_FROM] ?? null, 'year_from'),
            yearTo: $this->formatter->nullableInt($row[self::YEAR_TO] ?? null, 'year_to'),
            description: $this->formatter->requiredString($row[self::DESCRIPTION] ?? null, 'description'),
            powerPs: $this->formatter->requiredInt($row[self::POWER_PS] ?? null, 'power_ps'),
            powerKw: $this->formatter->requiredInt($row[self::POWER_KW] ?? null, 'power_kw'),
            engineType: $this->formatter->requiredString($row[self::ENGINE_TYPE] ?? null, 'engine_type'),
            gearType: $this->formatter->nullableString($row[self::GEAR_TYPE] ?? null),
            driveType: $this->formatter->nullableString($row[self::DRIVE_TYPE] ?? null),
            brakeSystemType: $this->formatter->nullableString($row[self::BRAKE_SYSTEM_TYPE] ?? null),
            numberOfCylinders: $this->formatter->nullableInt($row[self::NUMBER_OF_CYLINDERS] ?? null, 'number_of_cylinders'),
            capacityLt: $this->formatter->nullableFloat($row[self::CAPACITY_LT] ?? null, 'capacity_lt'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'type'),
        );
    }
}
