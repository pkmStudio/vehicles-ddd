<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
