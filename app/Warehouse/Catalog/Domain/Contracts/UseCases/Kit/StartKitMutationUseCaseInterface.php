<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit;

use App\Warehouse\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации Warehouse-набора из внешнего сообщения.
 */
interface StartKitMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации Warehouse-набора.
     */
    public function execute(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
