<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm;

final readonly class PackDimensionCrmListItemDTO
{
    /**
     * Фиксирует значения DTO без дополнительного поведения.
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $weight,
        public int $width,
        public int $height,
        public int $length,
        public int $price,
        public int $typeId,
        public string $typeName,
        public string $typeChar,
        public bool $generated,
        public int $kitsCount = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'price' => $this->price,
            'type_id' => $this->typeId,
            'type_name' => $this->typeName,
            'type_char' => $this->typeChar,
            'generated' => $this->generated,
            'kits_count' => $this->kitsCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
