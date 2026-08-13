<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-бренда.
 */
interface DeleteBrandUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-бренда.
     *
     * Шаги:
     * 1) Принять DeleteBrandRequestDTO из boundary handler.
     * 2) Удалить бренд с каскадной очисткой связанных номенклатур.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(DeleteBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
