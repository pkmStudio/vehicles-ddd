<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class VehicleSheetRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
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
