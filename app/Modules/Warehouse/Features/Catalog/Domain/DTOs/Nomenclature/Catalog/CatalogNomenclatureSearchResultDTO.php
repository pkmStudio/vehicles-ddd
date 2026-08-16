<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\CatalogNomenclatureSearchMatchEnum;
use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Support\Collection;

/**
 * Результат публичного поиска номенклатуры.
 */
final readonly class CatalogNomenclatureSearchResultDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит тип совпадения и найденные позиции.
     *
     * @param  Collection<int, CatalogNomenclatureSummaryDTO>  $items
     */
    public function __construct(
        public CatalogNomenclatureSearchMatchEnum $match,
        public Collection $items,
    ) {}

    /**
     * Возвращает HTTP-представление результата поиска.
     *
     * @return array{match: string, items: list<array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}>}
     */
    public function toArray(): array
    {
        $toArray = static fn (CatalogNomenclatureSummaryDTO $item): array => $item->toArray();

        return [
            'match' => $this->match->value,
            'items' => $this->items
                ->map($toArray)
                ->values()
                ->all(),
        ];
    }
}
