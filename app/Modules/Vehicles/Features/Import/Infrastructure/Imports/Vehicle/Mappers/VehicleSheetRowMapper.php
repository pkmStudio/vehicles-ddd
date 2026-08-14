<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку основного листа транспорта в DTO каталожного автомобиля.
 */
final readonly class VehicleSheetRowMapper
{
    private const int EXCEL_TABLE_ID = 0;

    private const int MFA_ID = 1;

    private const int MS_ID = 2;

    private const int MANUFACTURER_NAME = 3;

    private const int NAME = 4;

    private const int LOCALIZED_NAME = 5;

    private const int GENERATION_SHORT = 6;

    private const int GENERATION = 7;

    private const int GENERATION_YEAR_FROM = 8;

    private const int GENERATION_YEAR_TO = 9;

    private const int TYPE_CARCASE = 10;

    private const int TYPE = 11;

    private const int PROVIDER = 12;

    private const int PARENT_MS_ID = 13;

    private const int STEERING_TYPE = 14;

    private const int IS_ALLOW = 15;

    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при чтении идентификаторов, названий, годов и признаков ТС.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO ТС из строки основного листа импорта.
     *
     * Шаги:
     * 1) Прочитать идентификаторы, названия, поколение, кузов, тип и родительский ms_id.
     * 2) Нормализовать годы и идентификаторы как целые числа, а флаг допуска как «Да/Нет».
     * 3) Вернуть DTO для сервиса сохранения ТС из внешнего листа.
     *
     * @param  array<int, string|int|float|null>  $row
     *
     * @throws ImportRowValidationException
     */
    public function map(array $row): VehicleSheetRowDTO
    {
        return new VehicleSheetRowDTO(
            excelTableId: $this->formatter->nullableString($row[self::EXCEL_TABLE_ID] ?? null),
            mfaId: $this->formatter->nullableInt($row[self::MFA_ID] ?? null, 'mfa_id'),
            msId: $this->formatter->nullableInt($row[self::MS_ID] ?? null, 'ms_id'),
            manufacturerName: $this->formatter->requiredString($row[self::MANUFACTURER_NAME] ?? null, 'manufacturer_name'),
            name: $this->formatter->requiredString($row[self::NAME] ?? null, 'name'),
            localizedName: $this->formatter->nullableString($row[self::LOCALIZED_NAME] ?? null),
            generationShort: $this->formatter->nullableString($row[self::GENERATION_SHORT] ?? null),
            generation: $this->formatter->requiredString($row[self::GENERATION] ?? null, 'generation'),
            generationYearFrom: $this->formatter->requiredInt($row[self::GENERATION_YEAR_FROM] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[self::GENERATION_YEAR_TO] ?? null, 'generation_year_to'),
            typeCarcase: $this->formatter->requiredString($row[self::TYPE_CARCASE] ?? null, 'type_carcase'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'type'),
            provider: $this->formatter->requiredString($row[self::PROVIDER] ?? null, 'provider'),
            parentMsId: $this->formatter->nullableInt($row[self::PARENT_MS_ID] ?? null, 'parent_ms_id'),
            steeringType: $this->formatter->requiredString($row[self::STEERING_TYPE] ?? null, 'steering_type'),
            isAllow: $this->formatter->boolFromYesNo($row[self::IS_ALLOW] ?? null, 'is_allow'),
        );
    }
}
