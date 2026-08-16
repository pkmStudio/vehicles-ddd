<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

/**
 * Детальный сценарный снимок Warehouse-номенклатуры публичного каталога.
 */
final readonly class CatalogNomenclatureDTO
{
    /**
     * @param  list<string>  $material
     * @param  list<string>  $vehicleType
     * @param  array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>  $details
     */
    public function __construct(
        public string $partNumber,
        public string $name,
        public int $categoryId,
        public string $categoryName,
        public ?string $categoryCode,
        public int $brandId,
        public string $brandName,
        public ?string $brandCode,
        public string $country,
        public string $color,
        public int $weight,
        public array $material,
        public array $vehicleType,
        public int $quantityPak,
        public int $quantityInPak,
        public array $details,
    ) {}

}
