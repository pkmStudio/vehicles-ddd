<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления Warehouse-бренда.
 */
interface UpdateBrandUseCaseInterface
{
    /**
     * Выполняет обновление Warehouse-бренда.
     */
    public function execute(UpdateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
