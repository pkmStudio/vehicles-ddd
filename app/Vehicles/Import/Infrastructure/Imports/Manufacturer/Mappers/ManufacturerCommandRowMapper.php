<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Manufacturer\Mappers;

use App\Vehicles\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class ManufacturerCommandRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): ManufacturerCommandRowDTO
    {
        return new ManufacturerCommandRowDTO(
            mfaId: $this->formatter->nullableInt($row[0] ?? null, 'mfa_id'),
            name: $this->formatter->nullableString($row[1] ?? null),
        );
    }
}
