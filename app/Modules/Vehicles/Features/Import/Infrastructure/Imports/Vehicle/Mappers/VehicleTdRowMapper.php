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
    private const int MFA_ID = 0;

    private const int MS_ID = 1;

    private const int NAME = 2;

    private const int GENERATION = 3;

    private const int TYPE_CARCASE = 4;

    private const int GENERATION_YEAR_FROM = 5;

    private const int GENERATION_YEAR_TO = 6;

    private const int TYPE = 7;

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
            mfaId: $this->formatter->requiredInt($row[self::MFA_ID] ?? null, 'mfa_id'),
            msId: $this->formatter->requiredInt($row[self::MS_ID] ?? null, 'ms_id'),
            name: $this->formatter->requiredString($row[self::NAME] ?? null, 'name'),
            generation: $this->formatter->requiredString($row[self::GENERATION] ?? null, 'generation'),
            typeCarcase: $this->formatter->nullableString($row[self::TYPE_CARCASE] ?? null),
            generationYearFrom: $this->formatter->requiredInt($row[self::GENERATION_YEAR_FROM] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[self::GENERATION_YEAR_TO] ?? null, 'generation_year_to'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'type'),
        );
    }
}
