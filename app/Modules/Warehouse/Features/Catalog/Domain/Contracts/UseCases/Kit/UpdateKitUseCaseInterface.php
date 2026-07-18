<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
