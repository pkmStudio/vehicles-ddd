<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm;

final readonly class TypeCrmListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $char,
        public int $nomenclaturesCount = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    /**
     * @return array{id: int, name: string, char: string, nomenclatures_count: int, created_at: string|null, updated_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'char' => $this->char,
            'nomenclatures_count' => $this->nomenclaturesCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
