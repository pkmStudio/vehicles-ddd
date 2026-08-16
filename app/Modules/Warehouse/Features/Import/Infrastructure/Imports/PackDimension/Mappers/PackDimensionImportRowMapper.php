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
    private const int ID = 0;

    private const int NAME = 1;

    private const int WEIGHT = 2;

    private const int WIDTH = 3;

    private const int HEIGHT = 4;

    private const int LENGTH = 5;

    private const int PRICE = 6;

    private const int TYPE = 7;

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
            id: $this->formatter->nullableInt($row[self::ID] ?? null, 'ID'),
            name: $this->formatter->requiredString($row[self::NAME] ?? null, 'Название коробки'),
            weight: $this->formatter->positiveInt($row[self::WEIGHT] ?? null, 'Вес'),
            width: $this->formatter->positiveInt($row[self::WIDTH] ?? null, 'Ширина'),
            height: $this->formatter->positiveInt($row[self::HEIGHT] ?? null, 'Высота'),
            length: $this->formatter->positiveInt($row[self::LENGTH] ?? null, 'Длина'),
            price: $this->formatter->nonNegativeInt($row[self::PRICE] ?? null, 'Цена'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'Тип товара'),
        );
    }
}
