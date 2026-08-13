<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm;

final readonly class BrandCrmListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $numberSert,
        public string $dateStart,
        public string $dateEnd,
        public ?string $char,
        public int $nomenclaturesCount = 0,
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
            'number_sert' => $this->numberSert,
            'date_start' => $this->dateStart,
            'date_end' => $this->dateEnd,
            'char' => $this->char,
            'nomenclatures_count' => $this->nomenclaturesCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
