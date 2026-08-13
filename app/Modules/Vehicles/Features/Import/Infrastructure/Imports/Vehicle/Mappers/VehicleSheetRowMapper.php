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
            excelTableId: $this->formatter->nullableString($row[0] ?? null),
            mfaId: $this->formatter->nullableInt($row[1] ?? null, 'mfa_id'),
            msId: $this->formatter->nullableInt($row[2] ?? null, 'ms_id'),
            manufacturerName: $this->formatter->requiredString($row[3] ?? null, 'manufacturer_name'),
            name: $this->formatter->requiredString($row[4] ?? null, 'name'),
            localizedName: $this->formatter->nullableString($row[5] ?? null),
            generationShort: $this->formatter->nullableString($row[6] ?? null),
            generation: $this->formatter->requiredString($row[7] ?? null, 'generation'),
            generationYearFrom: $this->formatter->requiredInt($row[8] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[9] ?? null, 'generation_year_to'),
            typeCarcase: $this->formatter->requiredString($row[10] ?? null, 'type_carcase'),
            type: $this->formatter->requiredString($row[11] ?? null, 'type'),
            provider: $this->formatter->requiredString($row[12] ?? null, 'provider'),
            parentMsId: $this->formatter->nullableInt($row[13] ?? null, 'parent_ms_id'),
            steeringType: $this->formatter->requiredString($row[14] ?? null, 'steering_type'),
            isAllow: $this->formatter->boolFromYesNo($row[15] ?? null, 'is_allow'),
        );
    }
}
