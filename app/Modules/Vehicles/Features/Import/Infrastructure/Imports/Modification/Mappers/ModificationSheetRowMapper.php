<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Маппит строку manager Excel-листа модификаций в DTO с явными индексами колонок.
 */
final readonly class ModificationSheetRowMapper
{
    private const int MS_ID = 0;

    private const int MOD_ID = 1;

    private const int LOCALIZED_NAME = 2;

    private const int YEAR_FROM = 3;

    private const int YEAR_TO = 4;

    private const int CAPACITY_LT = 5;

    private const int ENGINE_TYPE = 6;

    private const int POWER_PS = 7;

    private const int POWER_KW = 8;

    private const int DRIVE_TYPE = 9;

    private const int GEAR_TYPE = 10;

    private const int BRAKE_SYSTEM_TYPE = 11;

    private const int NUMBER_OF_CYLINDERS = 12;

    private const int DESCRIPTION = 13;

    private const int DESCRIPTION_SHORT = 14;

    private const int TYPE = 15;

    /**
     * Получить нормализатор значений Excel-ячеек.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO строки модификации из фиксированных колонок.
     *
     * Шаги:
     * 1) Прочитать identifiers, названия, годы, характеристики и справочный type.
     * 2) Нормализовать scalar значения через ImportRowValueFormatter.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): ModificationSheetRowDTO
    {
        return new ModificationSheetRowDTO(
            msId: $this->formatter->requiredInt($row[self::MS_ID] ?? null, 'ms_id'),
            modId: $this->formatter->nullableInt($row[self::MOD_ID] ?? null, 'mod_id'),
            localizedName: $this->formatter->nullableString($row[self::LOCALIZED_NAME] ?? null),
            yearFrom: $this->formatter->requiredInt($row[self::YEAR_FROM] ?? null, 'year_from'),
            yearTo: $this->formatter->nullableInt($row[self::YEAR_TO] ?? null, 'year_to'),
            capacityLt: $this->formatter->nullableFloat($row[self::CAPACITY_LT] ?? null, 'capacity_lt'),
            engineType: $this->formatter->requiredString($row[self::ENGINE_TYPE] ?? null, 'engine_type'),
            powerPs: $this->formatter->requiredInt($row[self::POWER_PS] ?? null, 'power_ps'),
            powerKw: $this->formatter->requiredInt($row[self::POWER_KW] ?? null, 'power_kw'),
            driveType: $this->formatter->nullableString($row[self::DRIVE_TYPE] ?? null),
            gearType: $this->formatter->nullableString($row[self::GEAR_TYPE] ?? null),
            brakeSystemType: $this->formatter->nullableString($row[self::BRAKE_SYSTEM_TYPE] ?? null),
            numberOfCylinders: $this->formatter->nullableInt($row[self::NUMBER_OF_CYLINDERS] ?? null, 'number_of_cylinders'),
            description: $this->formatter->requiredString($row[self::DESCRIPTION] ?? null, 'description'),
            descriptionShort: $this->formatter->nullableString($row[self::DESCRIPTION_SHORT] ?? null),
            type: $this->formatter->nullableString($row[self::TYPE] ?? null),
        );
    }
}
