<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use Illuminate\Support\Collection;

/**
 * Страница номенклатуры одной плоской категории.
 */
final readonly class CatalogNomenclaturePageDTO
{
    /** @param Collection<int, CatalogNomenclatureSummaryDTO> $items */
    public function __construct(
        public CatalogCategoryDTO $category,
        public Collection $items,
        public int $total,
        public int $page,
        public int $pageSize,
        public int $pageCount,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category' => $this->category->toArray(),
            'items' => $this->items
                ->map(static fn (CatalogNomenclatureSummaryDTO $item): array => $item->toArray())
                ->values()
                ->all(),
            'total' => $this->total,
            'page' => $this->page,
            'page_size' => $this->pageSize,
            'page_count' => $this->pageCount,
        ];
    }
}
