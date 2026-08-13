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
     *
     * Шаги:
     * 1) Принять CreateKitRequestDTO из boundary handler.
     * 2) Рассчитать свойства комплекта по составу и создать kit.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(CreateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
