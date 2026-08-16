<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

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
        $type = $this->formatter->requiredString($row[self::TYPE] ?? null, 'type');
        $typeCarcase = $this->typeCarcase(
            type: $type,
            value: $this->formatter->nullableString($row[self::TYPE_CARCASE] ?? null),
        );

        return new VehicleTdRowDTO(
            mfaId: $this->formatter->requiredInt($row[self::MFA_ID] ?? null, 'mfa_id'),
            msId: $this->formatter->requiredInt($row[self::MS_ID] ?? null, 'ms_id'),
            name: $this->formatter->requiredString($row[self::NAME] ?? null, 'name'),
            generation: $this->formatter->requiredString($row[self::GENERATION] ?? null, 'generation'),
            typeCarcase: $typeCarcase,
            generationYearFrom: $this->formatter->requiredInt($row[self::GENERATION_YEAR_FROM] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[self::GENERATION_YEAR_TO] ?? null, 'generation_year_to'),
            type: $type,
        );
    }

    /**
     * Возвращает обязательный type_carcase для TecDoc vehicle row.
     *
     * Шаги:
     * 1) Если колонка заполнена — использовать значение TecDoc как есть.
     * 2) Если TecDoc не передал кузов для мототехники — явно проставить Motorcycle.
     * 3) Для остальных типов выбросить import validation error.
     *
     * @throws ImportRowValidationException
     */
    private function typeCarcase(string $type, ?string $value): string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        if ($type === VehicleTypeEnum::MB->value) {
            return CarcaseTypeEnum::MOTORCYCLE->value;
        }

        throw ImportRowValidationException::fromMessages([
            'type_carcase' => ['Поле type_carcase обязательно.'],
        ]);
    }
}
