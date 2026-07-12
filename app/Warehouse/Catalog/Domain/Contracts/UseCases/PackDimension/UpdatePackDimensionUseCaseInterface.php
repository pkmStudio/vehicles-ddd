<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления упаковочного размера Warehouse.
 */
interface UpdatePackDimensionUseCaseInterface
{
    /**
     * Выполняет обновление упаковочного размера Warehouse.
     */
    public function execute(UpdatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
