<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Support\Collection;

/**
 * Страница номенклатуры одной плоской категории.
 */
final readonly class CatalogNomenclaturePageDTO implements HttpArraySerializableInterface
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

    /**
     * Возвращает HTTP-представление страницы номенклатур.
     *
     * @return array{category: array{id: int, name: string, code: string|null, nomenclature_count: int}, items: list<array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}>, total: int, page: int, page_size: int, page_count: int}
     */
    public function toArray(): array
    {
        $toArray = static fn (CatalogNomenclatureSummaryDTO $item): array => $item->toArray();

        return [
            'category' => $this->category->toArray(),
            'items' => $this->items
                ->map($toArray)
                ->values()
                ->all(),
            'total' => $this->total,
            'page' => $this->page,
            'page_size' => $this->pageSize,
            'page_count' => $this->pageCount,
        ];
    }
}
