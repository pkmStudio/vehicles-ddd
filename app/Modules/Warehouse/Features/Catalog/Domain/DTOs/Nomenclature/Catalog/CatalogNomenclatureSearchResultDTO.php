<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\CatalogNomenclatureSearchMatchEnum;
use Illuminate\Support\Collection;

/**
 * Результат публичного поиска номенклатуры.
 */
final readonly class CatalogNomenclatureSearchResultDTO
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

}
