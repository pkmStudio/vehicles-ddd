<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm;

final readonly class KitCrmListItemDTO
{
    /**
     * @param  list<array{id: int, label: string, part_number: string}>  $nomenclatures
     */
    public function __construct(
        public int $id,
        public string $complectation,
        public int $guarantee,
        public int $quantityInPackage,
        public int $quantityPackage,
        public bool $complement,
        public int $weight,
        public int $packDimensionId,
        public ?string $packDimensionName,
        public int $typeId,
        public ?string $typeName,
        public ?string $typeChar,
        public ?string $importHash,
        public bool $isSaleSeparately,
        public bool $isActive,
        public int $nomenclaturesCount,
        public string $nomenclaturesList,
        public array $nomenclatures = [],
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
            'complectation' => $this->complectation,
            'guarantee' => $this->guarantee,
            'quantity_in_package' => $this->quantityInPackage,
            'quantity_package' => $this->quantityPackage,
            'complement' => $this->complement,
            'weight' => $this->weight,
            'pack_dimension_id' => $this->packDimensionId,
            'pack_dimension_name' => $this->packDimensionName,
            'type_id' => $this->typeId,
            'type_name' => $this->typeName,
            'type_char' => $this->typeChar,
            'import_hash' => $this->importHash,
            'is_sale_separately' => $this->isSaleSeparately,
            'is_active' => $this->isActive,
            'nomenclatures_count' => $this->nomenclaturesCount,
            'nomenclatures_list' => $this->nomenclaturesList,
            'nomenclatures' => $this->nomenclatures,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
