<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания Warehouse-номенклатуры.
 */
interface CreateNomenclatureUseCaseInterface
{
    /**
     * Выполняет создание Warehouse-номенклатуры.
     */
    public function execute(CreateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
