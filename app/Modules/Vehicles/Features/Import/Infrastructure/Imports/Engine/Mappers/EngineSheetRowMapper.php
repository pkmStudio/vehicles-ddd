<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Маппит строку командного Excel-импорта двигателей в DTO с явными индексами колонок.
 */
final readonly class EngineSheetRowMapper
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
     * Этот метод переводит одну строку командного engine-листа в `EngineSheetRowDTO`.
     *
     * Шаги:
     * 1) Прочитать значения по именованным индексам колонок командного листа.
     * 2) Нормализовать строки, целые и вещественные числа через `ImportRowValueFormatter`.
     * 3) Вернуть DTO, совместимый с общим сервисом сохранения двигателя.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineSheetRowDTO
    {
        $engId = $this->formatter->nullableInt(
            value: $row[self::ENG_ID] ?? null,
            field: 'eng_id',
        );
        $codeEngine = $this->formatter->nullableString($row[self::CODE_ENGINE] ?? null);
        $powerKwStart = $this->formatter->nullableInt(
            value: $row[self::POWER_KW_START] ?? null,
            field: 'power_kw_start',
        );
        $powerKwUpto = $this->formatter->nullableInt(
            value: $row[self::POWER_KW_UPTO] ?? null,
            field: 'power_kw_upto',
        );
        $powerPsStart = $this->formatter->nullableInt(
            value: $row[self::POWER_PS_START] ?? null,
            field: 'power_ps_start',
        );
        $powerPsUpto = $this->formatter->nullableInt(
            value: $row[self::POWER_PS_UPTO] ?? null,
            field: 'power_ps_upto',
        );
        $engineCapacity = $this->formatter->nullableString($row[self::ENGINE_CAPACITY] ?? null);
        $cylinderDiameter = $this->formatter->nullableFloat(
            value: $row[self::CYLINDER_DIAMETER] ?? null,
            field: 'cylinder_diameter',
        );
        $cylinderCount = $this->formatter->nullableInt(
            value: $row[self::CYLINDER_COUNT] ?? null,
            field: 'cylinder_count',
        );
        $numberOfValves = $this->formatter->nullableInt(
            value: $row[self::NUMBER_OF_VALVES] ?? null,
            field: 'number_of_valves',
        );
        $fuelType = $this->formatter->nullableString($row[self::FUEL_TYPE] ?? null);

        return new EngineSheetRowDTO(
            engId: $engId,
            codeEngine: $codeEngine,
            powerKwStart: $powerKwStart,
            powerKwUpto: $powerKwUpto,
            powerPsStart: $powerPsStart,
            powerPsUpto: $powerPsUpto,
            engineCapacity: $engineCapacity,
            cylinderDiameter: $cylinderDiameter,
            cylinderCount: $cylinderCount,
            numberOfValves: $numberOfValves,
            fuelType: $fuelType,
        );
    }
}
