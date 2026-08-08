<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use Illuminate\Support\Collection;

interface ListCatalogCategoriesUseCaseInterface
{
    /** @return Collection<int, CatalogCategoryDTO> */
    public function execute(int $brandId): Collection;
}
