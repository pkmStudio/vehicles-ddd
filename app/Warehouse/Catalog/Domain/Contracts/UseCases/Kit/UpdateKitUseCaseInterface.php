<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit;

use App\Warehouse\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления Warehouse-набора.
 */
interface UpdateKitUseCaseInterface
{
    /**
     * Выполняет обновление Warehouse-набора.
     */
    public function execute(UpdateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
