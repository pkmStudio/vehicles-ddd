<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\PackDimension\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\PackDimension\PackDimensionImportRowDTO;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит Excel-строку упаковки в typed row DTO.
 */
final readonly class PackDimensionImportRowMapper
{
    private ImportRowValueFormatter $formatter;

    public function __construct(?ImportRowValueFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new ImportRowValueFormatter;
    }

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): PackDimensionImportRowDTO
    {
        return new PackDimensionImportRowDTO(
            id: $this->formatter->nullableInt($row[0] ?? null, 'ID'),
            name: $this->formatter->requiredString($row[1] ?? null, 'Название коробки'),
            weight: $this->formatter->positiveInt($row[2] ?? null, 'Вес'),
            width: $this->formatter->positiveInt($row[3] ?? null, 'Ширина'),
            height: $this->formatter->positiveInt($row[4] ?? null, 'Высота'),
            length: $this->formatter->positiveInt($row[5] ?? null, 'Длина'),
            price: $this->formatter->nonNegativeInt($row[6] ?? null, 'Цена'),
            type: $this->formatter->requiredString($row[7] ?? null, 'Тип товара'),
        );
    }
}
