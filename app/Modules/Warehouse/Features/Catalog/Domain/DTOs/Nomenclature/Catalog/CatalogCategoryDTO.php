<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

/**
 * Публичная REST-проекция плоской категории номенклатуры.
 */
final readonly class CatalogCategoryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public int $nomenclatureCount,
    ) {}

    /** @return array{id: int, name: string, code: string|null, nomenclature_count: int} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'nomenclature_count' => $this->nomenclatureCount,
        ];
    }
}
