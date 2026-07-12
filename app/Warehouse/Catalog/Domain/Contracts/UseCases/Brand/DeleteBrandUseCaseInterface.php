<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand;

use App\Warehouse\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-бренда.
 */
interface DeleteBrandUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-бренда.
     */
    public function execute(DeleteBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
