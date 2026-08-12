<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации упаковочного размера Warehouse из внешнего сообщения.
 */
interface StartPackDimensionMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации упаковочного размера Warehouse.
     *
     * Шаги:
     * 1) Принять общий PackDimensionMutationRequestDTO.
     * 2) Выбрать create/update/delete сценарий по operation.
     * 3) Вернуть result DTO выбранного сценария или null для повтора.
     */
    public function execute(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
