<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class VehicleTdRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): VehicleTdRowDTO
    {
        return new VehicleTdRowDTO(
            mfaId: $this->formatter->nullableInt($row[0] ?? null, 'mfa_id'),
            msId: $this->formatter->nullableInt($row[1] ?? null, 'ms_id'),
            name: $this->formatter->nullableString($row[2] ?? null),
            generation: $this->formatter->nullableString($row[3] ?? null),
            typeCarcase: $this->formatter->nullableString($row[4] ?? null),
            generationYearFrom: $this->formatter->nullableInt($row[5] ?? null, 'generation_year_from'),
            generationYearTo: $this->formatter->nullableInt($row[6] ?? null, 'generation_year_to'),
            type: $this->formatter->nullableString($row[7] ?? null),
        );
    }
}
