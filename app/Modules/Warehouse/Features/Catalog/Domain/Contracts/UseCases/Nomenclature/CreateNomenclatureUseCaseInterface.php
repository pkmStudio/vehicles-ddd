<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
