<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

/**
 * Сценарный снимок плоской категории номенклатуры публичного каталога.
 */
final readonly class CatalogCategoryDTO
{
    /**
     * Хранит поля категории и число доступных позиций выбранного бренда.
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public int $nomenclatureCount,
    ) {}

}
