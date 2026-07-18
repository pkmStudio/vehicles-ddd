<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
