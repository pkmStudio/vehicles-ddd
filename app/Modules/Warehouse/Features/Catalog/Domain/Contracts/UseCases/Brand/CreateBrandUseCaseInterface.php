<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
