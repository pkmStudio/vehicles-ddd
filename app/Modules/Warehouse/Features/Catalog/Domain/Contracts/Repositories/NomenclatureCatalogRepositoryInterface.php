<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для публичного каталога dan-catalog.
 */
interface NomenclatureCatalogRepositoryInterface
{
    /**
     * Возвращает непустые категории выбранного бренда.
     *
     * @return Collection<int, CatalogCategoryDTO>
     */
    public function categories(int $brandId): Collection;

    /**
     * Возвращает категорию с количеством номенклатур выбранного бренда.
     */
    public function findCategory(int $categoryId, int $brandId): ?CatalogCategoryDTO;

    /**
     * Возвращает элементы запрошенной страницы категории.
     *
     * @return Collection<int, CatalogNomenclatureSummaryDTO>
     */
    public function findByCategory(int $categoryId, int $brandId, int $page, int $pageSize): Collection;

    /**
     * Ищет детальную номенклатуру бренда по артикулу без учета регистра.
     */
    public function findByPartNumber(string $partNumber, int $brandId): ?CatalogNomenclatureDTO;

    /**
     * Ищет номенклатуры бренда по артикулу и имени.
     *
     * @return Collection<int, CatalogNomenclatureSummaryDTO>
     */
    public function search(string $query, int $brandId, int $limit): Collection;
}
