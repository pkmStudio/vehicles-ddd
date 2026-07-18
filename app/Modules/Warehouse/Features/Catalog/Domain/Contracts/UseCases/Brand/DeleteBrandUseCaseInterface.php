<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
