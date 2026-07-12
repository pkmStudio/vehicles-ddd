<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания упаковочного размера Warehouse.
 */
interface CreatePackDimensionUseCaseInterface
{
    /**
     * Выполняет создание упаковочного размера Warehouse.
     */
    public function execute(CreatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
