<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
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
     */
    public function map(array $row): VehicleSheetRowDTO
    {
        return new VehicleSheetRowDTO(
            excelTableId: $this->formatter->nullableString($row[0] ?? null),
            mfaId: $this->formatter->nullableInt($row[1] ?? null, 'mfa_id'),
            msId: $this->formatter->nullableInt($row[2] ?? null, 'ms_id'),
            manufacturerName: $this->formatter->nullableString($row[3] ?? null),
            name: $this->formatter->nullableString($row[4] ?? null),
            localizedName: $this->formatter->nullableString($row[5] ?? null),
            generationShort: $this->formatter->nullableString($row[6] ?? null),
            generation: $this->formatter->nullableString($row[7] ?? null),
            generationYearFrom: $this->formatter->nullableInt($row[8] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[9] ?? null, 'generation_year_to'),
            typeCarcase: $this->formatter->nullableString($row[10] ?? null),
            type: $this->formatter->nullableString($row[11] ?? null),
            provider: $this->formatter->nullableString($row[12] ?? null),
            parentMsId: $this->formatter->nullableInt($row[13] ?? null, 'parent_ms_id'),
            steeringType: $this->formatter->nullableString($row[14] ?? null),
            isAllow: $this->formatter->nullableBoolFromYesNo($row[15] ?? null, 'is_allow'),
        );
    }
}
