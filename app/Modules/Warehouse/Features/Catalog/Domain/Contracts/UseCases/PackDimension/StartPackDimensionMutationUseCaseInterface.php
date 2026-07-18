<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
