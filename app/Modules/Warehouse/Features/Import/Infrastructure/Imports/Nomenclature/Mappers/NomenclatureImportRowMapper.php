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
    private const int ID = 0;

    private const int TYPE_NAME = 1;

    private const int BRAND_NAME = 2;

    private const int NAME = 3;

    private const int COUNTRY = 4;

    private const int PART_NUMBER = 5;

    private const int COLOR = 6;

    private const int WEIGHT = 7;

    private const int MATERIAL_LABELS = 8;

    private const int VEHICLE_TYPE_LABELS = 9;

    private const int QUANTITY_PAK = 10;

    private const int QUANTITY_IN_PAK = 11;

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
            id: $this->formatter->nullableInt($row[self::ID] ?? null, 'ID'),
            typeName: $this->formatter->requiredString($row[self::TYPE_NAME] ?? null, 'Тип товара'),
            brandName: $this->formatter->requiredString($row[self::BRAND_NAME] ?? null, 'Бренд'),
            name: $this->formatter->requiredString($row[self::NAME] ?? null, 'Название'),
            country: $this->formatter->requiredString($row[self::COUNTRY] ?? null, 'Страна'),
            partNumber: $this->formatter->requiredString($row[self::PART_NUMBER] ?? null, 'Артикул'),
            color: $this->formatter->requiredString($row[self::COLOR] ?? null, 'Цвет'),
            weight: $this->formatter->positiveInt($row[self::WEIGHT] ?? null, 'Вес'),
            materialLabels: $this->formatter->requiredString($row[self::MATERIAL_LABELS] ?? null, 'Материал'),
            vehicleTypeLabels: $this->formatter->requiredString($row[self::VEHICLE_TYPE_LABELS] ?? null, 'Тип ТС'),
            quantityPak: $this->formatter->positiveInt($row[self::QUANTITY_PAK] ?? null, 'Кол-во упаковок'),
            quantityInPak: $this->formatter->positiveInt($row[self::QUANTITY_IN_PAK] ?? null, 'Кол-во шт в упаковке'),
            sourceCells: $row,
        );
    }
}
