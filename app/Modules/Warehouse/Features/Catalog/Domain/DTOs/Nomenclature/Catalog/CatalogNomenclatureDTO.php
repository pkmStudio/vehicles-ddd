<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Детальный сценарный снимок Warehouse-номенклатуры публичного каталога.
 */
final readonly class CatalogNomenclatureDTO implements HttpArraySerializableInterface
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

    /**
     * Возвращает HTTP-представление детальной номенклатуры.
     *
     * @return array{part_number: string, name: string, category_id: int, category_name: string, category_code: string|null, brand_id: int, brand_name: string, brand_code: string|null, country: string, color: string, weight: int, material: list<string>, vehicle_type: list<string>, quantity_pak: int, quantity_in_pak: int, details: array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>}
     */
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
