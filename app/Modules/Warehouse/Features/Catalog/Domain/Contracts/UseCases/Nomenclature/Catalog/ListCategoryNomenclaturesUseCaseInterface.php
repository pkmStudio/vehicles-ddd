<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;

interface ListCategoryNomenclaturesUseCaseInterface
{
    public function execute(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO;
}
