<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации Warehouse-номенклатуры из внешнего сообщения.
 */
interface StartNomenclatureMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации Warehouse-номенклатуры.
     */
    public function execute(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
