<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;

interface SearchCatalogNomenclaturesUseCaseInterface
{
    public function execute(string $query, int $brandId, int $limit): CatalogNomenclatureSearchResultDTO;
}
