<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку командного импорта ТС в DTO TecDoc-формата.
 */
final readonly class VehicleTdRowMapper
{
    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при чтении идентификаторов, названия, поколения и типа кузова.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO ТС из строки командного импорта.
     *
     * Шаги:
     * 1) Прочитать mfa_id, ms_id, название, поколение, кузов, годы выпуска и тип.
     * 2) Нормализовать идентификаторы и годы как целые числа, остальные поля как строки.
     * 3) Вернуть DTO для построчного сервиса сохранения ТС.
     *
     * @param  array<int, string|int|float|null>  $row
     *
     * @throws ImportRowValidationException
     */
    public function map(array $row): VehicleTdRowDTO
    {
        return new VehicleTdRowDTO(
            mfaId: $this->formatter->requiredInt($row[0] ?? null, 'mfa_id'),
            msId: $this->formatter->requiredInt($row[1] ?? null, 'ms_id'),
            name: $this->formatter->requiredString($row[2] ?? null, 'name'),
            generation: $this->formatter->requiredString($row[3] ?? null, 'generation'),
            typeCarcase: $this->formatter->nullableString($row[4] ?? null),
            generationYearFrom: $this->formatter->requiredInt($row[5] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[6] ?? null, 'generation_year_to'),
            type: $this->formatter->requiredString($row[7] ?? null, 'type'),
        );
    }
}
