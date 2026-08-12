<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Маппит строку основного Excel-листа двигателей в DTO с явными индексами колонок.
 */
final readonly class EngineMainSheetRowMapper
{
    private const int ENG_ID = 0;

    private const int CODE_ENGINE = 1;

    private const int ENGINE_CAPACITY = 2;

    private const int POWER_PS_START = 4;

    private const int POWER_PS_UPTO = 5;

    private const int CYLINDER_COUNT = 6;

    private const int CYLINDER_DIAMETER = 7;

    private const int NUMBER_OF_VALVES = 8;

    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Этот метод переводит одну строку основного листа двигателей в `EngineSheetRowDTO`.
     * Шаги:
     * 1) Прочитать значения по именованным индексам колонок основного листа.
     * 2) Нормализовать scalar values через `ImportRowValueFormatter`.
     * 3) Вернуть DTO, совместимый с общим engine upsert service.
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

        return new EngineSheetRowDTO(
            engId: $engId,
            codeEngine: $codeEngine,
            powerKwStart: null,
            powerKwUpto: null,
            powerPsStart: $powerPsStart,
            powerPsUpto: $powerPsUpto,
            engineCapacity: $engineCapacity,
            cylinderDiameter: $cylinderDiameter,
            cylinderCount: $cylinderCount,
            numberOfValves: $numberOfValves,
            fuelType: null,
        );
    }
}
