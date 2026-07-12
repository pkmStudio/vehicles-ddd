<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации упаковочного размера Warehouse из внешнего сообщения.
 */
interface StartPackDimensionMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации упаковочного размера Warehouse.
     */
    public function execute(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
