<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания упаковочного размера Warehouse.
 */
interface CreatePackDimensionUseCaseInterface
{
    /**
     * Выполняет создание упаковочного размера Warehouse.
     *
     * Шаги:
     * 1) Принять CreatePackDimensionRequestDTO из boundary handler.
     * 2) Создать упаковочный размер после проверки связанного type.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(CreatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
