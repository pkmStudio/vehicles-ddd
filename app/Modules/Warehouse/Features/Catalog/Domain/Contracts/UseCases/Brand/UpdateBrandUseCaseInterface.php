<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления Warehouse-бренда.
 */
interface UpdateBrandUseCaseInterface
{
    /**
     * Выполняет обновление Warehouse-бренда.
     *
     * Шаги:
     * 1) Принять UpdateBrandRequestDTO из boundary handler.
     * 2) Обновить бренд или отклонить операцию при not found/name conflict.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(UpdateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
