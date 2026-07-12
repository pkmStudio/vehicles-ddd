<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-номенклатуры.
 */
interface DeleteNomenclatureUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-номенклатуры.
     */
    public function execute(DeleteNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
