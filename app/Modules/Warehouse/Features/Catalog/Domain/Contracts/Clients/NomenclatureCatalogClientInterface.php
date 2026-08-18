<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read-only клиент Warehouse-номенклатуры публичного каталога.
 */
interface NomenclatureCatalogClientInterface
{
    /**
     * Возвращает непустые категории выбранного бренда.
     *
     * @return Collection<int, CatalogCategoryDTO>
     */
    public function categories(int $brandId): Collection;

    /**
     * Возвращает страницу номенклатур категории или null для неизвестной категории.
     */
    public function nomenclatures(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO;

    /**
     * Возвращает детальную номенклатуру бренда по артикулу.
     */
    public function nomenclature(string $partNumber, int $brandId): ?CatalogNomenclatureDTO;

    /**
     * Ищет номенклатуры бренда по артикулу и имени.
     */
    public function search(string $query, int $brandId, int $limit): CatalogNomenclatureSearchResultDTO;
}
