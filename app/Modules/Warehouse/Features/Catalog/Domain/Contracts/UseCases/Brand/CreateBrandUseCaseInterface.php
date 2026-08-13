<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания Warehouse-бренда.
 */
interface CreateBrandUseCaseInterface
{
    /**
     * Выполняет создание Warehouse-бренда.
     *
     * Шаги:
     * 1) Принять CreateBrandRequestDTO из boundary handler.
     * 2) Создать бренд или отклонить операцию при конфликте имени.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(CreateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
