<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class EngineModificationCommandRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineModificationCommandRowDTO
    {
        return new EngineModificationCommandRowDTO(
            engId: $this->formatter->nullableInt($row[0] ?? null, 'eng_id'),
            modId: $this->formatter->nullableInt($row[1] ?? null, 'mod_id'),
            type: $this->formatter->nullableString($row[2] ?? null),
        );
    }
}
