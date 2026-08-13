<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\Nomenclature\NomenclatureImportRowDTO;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит Excel-строку номенклатуры в typed row DTO.
 */
final readonly class NomenclatureImportRowMapper
{
    private ImportRowValueFormatter $formatter;

    public function __construct(?ImportRowValueFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new ImportRowValueFormatter;
    }

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): NomenclatureImportRowDTO
    {
        return new NomenclatureImportRowDTO(
            id: $this->formatter->nullableInt($row[0] ?? null, 'ID'),
            typeName: $this->formatter->requiredString($row[1] ?? null, 'Тип товара'),
            brandName: $this->formatter->requiredString($row[2] ?? null, 'Бренд'),
            name: $this->formatter->requiredString($row[3] ?? null, 'Название'),
            country: $this->formatter->requiredString($row[4] ?? null, 'Страна'),
            partNumber: $this->formatter->requiredString($row[5] ?? null, 'Артикул'),
            color: $this->formatter->requiredString($row[6] ?? null, 'Цвет'),
            weight: $this->formatter->positiveInt($row[7] ?? null, 'Вес'),
            materialLabels: $this->formatter->nullableString($row[8] ?? null) ?? '',
            vehicleTypeLabels: $this->formatter->nullableString($row[9] ?? null) ?? '',
            quantityPak: $this->formatter->positiveInt($row[10] ?? null, 'Кол-во упаковок'),
            quantityInPak: $this->formatter->positiveInt($row[11] ?? null, 'Кол-во шт в упаковке'),
            sourceCells: $row,
        );
    }
}
