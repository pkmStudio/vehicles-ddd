<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit;

use App\Warehouse\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания Warehouse-набора.
 */
interface CreateKitUseCaseInterface
{
    /**
     * Выполняет создание Warehouse-набора.
     */
    public function execute(CreateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
