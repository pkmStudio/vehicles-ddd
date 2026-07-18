<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
