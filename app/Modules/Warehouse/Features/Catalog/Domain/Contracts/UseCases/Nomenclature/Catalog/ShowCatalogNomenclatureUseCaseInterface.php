<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;

interface ShowCatalogNomenclatureUseCaseInterface
{
    public function execute(string $partNumber, int $brandId): ?CatalogNomenclatureDTO;
}
