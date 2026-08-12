<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации Warehouse-номенклатуры из внешнего сообщения.
 */
interface StartNomenclatureMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации Warehouse-номенклатуры.
     *
     * Шаги:
     * 1) Принять общий NomenclatureMutationRequestDTO.
     * 2) Выбрать create/update/delete сценарий по operation.
     * 3) Вернуть result DTO выбранного сценария или null для повтора.
     */
    public function execute(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
