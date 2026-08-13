<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария обновления Warehouse-номенклатуры.
 */
interface UpdateNomenclatureUseCaseInterface
{
    /**
     * Выполняет обновление Warehouse-номенклатуры.
     *
     * Шаги:
     * 1) Принять UpdateNomenclatureRequestDTO из boundary handler.
     * 2) Обновить номенклатуру после проверки type/brand и part_number conflict.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(UpdateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
