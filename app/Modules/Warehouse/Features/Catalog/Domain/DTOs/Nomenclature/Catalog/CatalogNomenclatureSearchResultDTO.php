<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use Illuminate\Support\Collection;

/**
 * Результат публичного поиска номенклатуры.
 */
final readonly class CatalogNomenclatureSearchResultDTO
{
    /** @param Collection<int, CatalogNomenclatureSummaryDTO> $items */
    public function __construct(
        public string $match,
        public Collection $items,
    ) {}

    /** @return array{match: string, items: list<array<string, int|string>>} */
    public function toArray(): array
    {
        return [
            'match' => $this->match,
            'items' => $this->items
                ->map(static fn (CatalogNomenclatureSummaryDTO $item): array => $item->toArray())
                ->values()
                ->all(),
        ];
    }
}
