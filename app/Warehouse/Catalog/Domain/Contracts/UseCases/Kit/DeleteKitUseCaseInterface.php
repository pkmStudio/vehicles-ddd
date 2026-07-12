<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit;

use App\Warehouse\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-набора.
 */
interface DeleteKitUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-набора.
     */
    public function execute(DeleteKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
