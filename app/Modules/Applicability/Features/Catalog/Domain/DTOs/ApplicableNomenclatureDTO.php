<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

/**
 * Краткая проекция применимого товара для frontend-каталога.
 */
final readonly class ApplicableNomenclatureDTO
{
    public function __construct(
        public string $partNumber,
        public string $name,
        public int $categoryId,
        public int $brandId,
        public string $brandName,
    ) {}

    /** @return array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string} */
    public function toArray(): array
    {
        return [
            'part_number' => $this->partNumber,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
        ];
    }
}
