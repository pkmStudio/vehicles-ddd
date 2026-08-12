<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления упаковочного размера Warehouse.
 */
interface UpdatePackDimensionUseCaseInterface
{
    /**
     * Выполняет обновление упаковочного размера Warehouse.
     *
     * Шаги:
     * 1) Принять UpdatePackDimensionRequestDTO из boundary handler.
     * 2) Обновить упаковочный размер после проверки записи и связанного type.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(UpdatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
