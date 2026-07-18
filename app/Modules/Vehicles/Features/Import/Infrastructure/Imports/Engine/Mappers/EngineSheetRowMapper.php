<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class EngineSheetRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineSheetRowDTO
    {
        return new EngineSheetRowDTO(
            engId: $this->formatter->nullableInt($row[0] ?? null, 'eng_id'),
            codeEngine: $this->formatter->nullableString($row[1] ?? null),
            engPowerKwStart: $this->formatter->nullableInt($row[2] ?? null, 'eng_power_kw_start'),
            engPowerKwUpto: $this->formatter->nullableInt($row[3] ?? null, 'eng_power_kw_upto'),
            engPowerPsStart: $this->formatter->nullableInt($row[4] ?? null, 'eng_power_ps_start'),
            engPowerPsUpto: $this->formatter->nullableInt($row[5] ?? null, 'eng_power_ps_upto'),
            engineCapacity: $this->formatter->nullableString($row[6] ?? null),
            cylinderDiameter: $this->formatter->nullableFloat($row[7] ?? null, 'cylinder_diameter'),
            cylinderCount: $this->formatter->nullableInt($row[8] ?? null, 'cylinder_count'),
            engNumberOfValves: $this->formatter->nullableInt($row[9] ?? null, 'eng_number_of_valves'),
            engFuelType: $this->formatter->nullableString($row[10] ?? null),
        );
    }
}
