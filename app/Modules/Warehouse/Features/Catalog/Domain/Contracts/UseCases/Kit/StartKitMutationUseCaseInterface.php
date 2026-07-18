<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
