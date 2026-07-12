<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand;

use App\Warehouse\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
