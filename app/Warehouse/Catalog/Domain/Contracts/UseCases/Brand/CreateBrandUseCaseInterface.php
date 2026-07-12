<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand;

use App\Warehouse\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания Warehouse-бренда.
 */
interface CreateBrandUseCaseInterface
{
    /**
     * Выполняет создание Warehouse-бренда.
     */
    public function execute(CreateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
