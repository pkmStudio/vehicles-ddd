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
     *
     * Шаги:
     * 1) Принять DeletePackDimensionRequestDTO из boundary handler.
     * 2) Удалить упаковочный размер с каскадной очисткой связанных комплектов.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(DeletePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
