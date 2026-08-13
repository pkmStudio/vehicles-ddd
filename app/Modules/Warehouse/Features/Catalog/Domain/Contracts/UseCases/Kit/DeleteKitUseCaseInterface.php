<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-набора.
 */
interface DeleteKitUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-набора.
     *
     * Шаги:
     * 1) Принять DeleteKitRequestDTO из boundary handler.
     * 2) Удалить kit вместе с pivot-составом.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(DeleteKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
