<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

use Illuminate\Support\Collection;

/**
 * Пагинированная страница применимых товаров выбранной категории.
 */
final readonly class ApplicableNomenclaturePageDTO
{
    /** @param Collection<int, ApplicableNomenclatureDTO> $items */
    public function __construct(
        public ApplicableCategoryDTO $category,
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
                ->map(static fn (ApplicableNomenclatureDTO $item): array => $item->toArray())
                ->values()
                ->all(),
            'total' => $this->total,
            'page' => $this->page,
            'page_size' => $this->pageSize,
            'page_count' => $this->pageCount,
        ];
    }
}
