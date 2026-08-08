<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для публичного каталога dan-catalog.
 */
interface NomenclatureCatalogRepositoryInterface
{
    /** @return Collection<int, CatalogCategoryDTO> */
    public function categories(int $brandId): Collection;

    public function paginateByCategory(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO;

    public function findByPartNumber(string $partNumber, int $brandId): ?CatalogNomenclatureDTO;

    /** @return Collection<int, CatalogNomenclatureSummaryDTO> */
    public function search(string $query, int $brandId, int $limit): Collection;
}
