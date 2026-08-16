<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Сценарный снимок Warehouse-номенклатуры для CRM read API.
 */
final readonly class NomenclatureCrmListItemDTO implements HttpArraySerializableInterface
{
    /**
     * @param  array<int, string>  $material
     * @param  array<int, string>  $vehicleType
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $id,
        public int $typeId,
        public string $typeName,
        public string $typeChar,
        public ?string $typeTemplate,
        public int $brandId,
        public string $brandName,
        public string $brandChar,
        public string $name,
        public string $country,
        public string $partNumber,
        public string $color,
        public int $weight,
        public array $material,
        public array $vehicleType,
        public int $quantityPak,
        public int $quantityInPak,
        public array $details,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->typeId,
            'type_name' => $this->typeName,
            'type_char' => $this->typeChar,
            'type_template' => $this->typeTemplate,
            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
            'brand_char' => $this->brandChar,
            'name' => $this->name,
            'country' => $this->country,
            'part_number' => $this->partNumber,
            'color' => $this->color,
            'weight' => $this->weight,
            'material' => $this->material,
            'vehicle_type' => $this->vehicleType,
            'quantity_pak' => $this->quantityPak,
            'quantity_in_pak' => $this->quantityInPak,
            'details' => $this->details,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
