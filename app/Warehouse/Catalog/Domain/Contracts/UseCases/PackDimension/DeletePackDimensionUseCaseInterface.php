<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления упаковочного размера Warehouse.
 */
interface DeletePackDimensionUseCaseInterface
{
    /**
     * Выполняет удаление упаковочного размера Warehouse.
     */
    public function execute(DeletePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
