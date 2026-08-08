<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

/**
 * Детальная публичная REST-проекция Warehouse-номенклатуры.
 */
final readonly class CatalogNomenclatureDTO
{
    /**
     * @param  array<int, string>  $material
     * @param  array<int, string>  $vehicleType
     * @param  array<string, mixed>  $details
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'part_number' => $this->partNumber,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_code' => $this->categoryCode,
            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
            'brand_code' => $this->brandCode,
            'country' => $this->country,
            'color' => $this->color,
            'weight' => $this->weight,
            'material' => $this->material,
            'vehicle_type' => $this->vehicleType,
            'quantity_pak' => $this->quantityPak,
            'quantity_in_pak' => $this->quantityInPak,
            'details' => $this->details,
        ];
    }
}
