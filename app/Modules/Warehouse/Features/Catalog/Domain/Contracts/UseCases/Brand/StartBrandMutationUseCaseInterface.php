<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт стартового сценария мутации Warehouse-бренда из внешнего сообщения.
 */
interface StartBrandMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации Warehouse-бренда.
     */
    public function execute(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}
