<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs\Nomenclature;

/**
 * Валидированная базовая строка импорта Warehouse-номенклатуры.
 */
final readonly class NomenclatureImportRowDTO
{
    /**
     * @param  array<int, string|int|float|null>  $sourceCells
     */
    public function __construct(
        public ?int $id,
        public string $typeName,
        public string $brandName,
        public string $name,
        public string $country,
        public string $partNumber,
        public string $color,
        public int $weight,
        public string $materialLabels,
        public string $vehicleTypeLabels,
        public int $quantityPak,
        public int $quantityInPak,
        public array $sourceCells,
    ) {}
}
