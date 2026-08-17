<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineTdRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Маппит строку командного Excel-импорта двигателей в DTO с явными индексами колонок.
 */
final readonly class EngineTdRowMapper
{
    private const int ENG_ID = 0;

    private const int CODE_ENGINE = 1;

    private const int POWER_KW_START = 2;

    private const int POWER_KW_UPTO = 3;

    private const int POWER_PS_START = 4;

    private const int POWER_PS_UPTO = 5;

    private const int ENGINE_CAPACITY = 6;

    private const int CYLINDER_DIAMETER = 7;

    private const int CYLINDER_COUNT = 8;

    private const int NUMBER_OF_VALVES = 9;

    private const int FUEL_TYPE = 10;

    /**
     * Получить нормализатор значений командного листа двигателей.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при чтении мощности, объёма, цилиндров, клапанов и типа топлива.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Этот метод переводит одну строку командного engine-листа в `EngineTdRowDTO`.
     *
     * Шаги:
     * 1) Прочитать значения по именованным индексам колонок командного листа.
     * 2) Нормализовать строки, целые и вещественные числа через `ImportRowValueFormatter`.
     * 3) Вернуть DTO, совместимый с общим сервисом сохранения двигателя.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineTdRowDTO
    {
        return new EngineTdRowDTO(
            engId: $this->formatter->requiredInt($row[self::ENG_ID] ?? null, 'eng_id'),
            codeEngine: $this->formatter->requiredString($row[self::CODE_ENGINE] ?? null, 'code_engine'),
            powerKwStart: $this->formatter->requiredInt($row[self::POWER_KW_START] ?? null, 'power_kw_start'),
            powerPsStart: $this->formatter->requiredInt($row[self::POWER_PS_START] ?? null, 'power_ps_start'),
            fuelType: $this->formatter->requiredString($row[self::FUEL_TYPE] ?? null, 'fuel_type'),
            powerKwUpto: $this->formatter->nullableInt($row[self::POWER_KW_UPTO] ?? null, 'power_kw_upto'),
            powerPsUpto: $this->formatter->nullableInt($row[self::POWER_PS_UPTO] ?? null, 'power_ps_upto'),
            engineCapacity: $this->formatter->nullableFloat($row[self::ENGINE_CAPACITY] ?? null, 'engine_capacity'),
            cylinderDiameter: $this->formatter->nullableFloat($row[self::CYLINDER_DIAMETER] ?? null, 'cylinder_diameter'),
            cylinderCount: $this->formatter->nullableInt($row[self::CYLINDER_COUNT] ?? null, 'cylinder_count'),
            numberOfValves: $this->formatter->nullableInt($row[self::NUMBER_OF_VALVES] ?? null, 'number_of_valves'),
        );
    }
}
