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
     *
     * Шаги:
     * 1) Принять UpdateKitRequestDTO из boundary handler.
     * 2) Пересчитать свойства и обновить kit при отсутствии import_hash conflict.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(UpdateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
