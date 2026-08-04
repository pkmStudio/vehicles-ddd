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
     */
    public function execute(UpdateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
