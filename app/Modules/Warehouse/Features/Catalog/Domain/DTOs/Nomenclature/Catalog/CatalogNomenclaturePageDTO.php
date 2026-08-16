<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use Illuminate\Support\Collection;

/**
 * Страница номенклатуры одной плоской категории.
 */
final readonly class CatalogNomenclaturePageDTO
{
    /**
     * Хранит категорию, элементы и метаданные пагинации.
     *
     * @param  Collection<int, CatalogNomenclatureSummaryDTO>  $items
     */
    public function __construct(
        public CatalogCategoryDTO $category,
        public Collection $items,
        public int $total,
        public int $page,
        public int $pageSize,
        public int $pageCount,
    ) {}

}
