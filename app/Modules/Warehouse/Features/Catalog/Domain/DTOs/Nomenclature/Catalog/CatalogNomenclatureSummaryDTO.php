<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Краткий сценарный снимок Warehouse-номенклатуры публичного каталога.
 */
final readonly class CatalogNomenclatureSummaryDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит поля позиции, необходимые списку и поиску.
     */
    public function __construct(
        public string $partNumber,
        public string $name,
        public int $categoryId,
        public int $brandId,
        public string $brandName,
    ) {}

    /**
     * Возвращает HTTP-представление краткой номенклатуры.
     *
     * @return array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}
     */
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
