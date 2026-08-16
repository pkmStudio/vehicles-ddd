<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

/**
 * Краткий сценарный снимок Warehouse-номенклатуры публичного каталога.
 */
final readonly class CatalogNomenclatureSummaryDTO
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

}
