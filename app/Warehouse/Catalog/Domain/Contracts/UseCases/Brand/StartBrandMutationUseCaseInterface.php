<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand;

use App\Warehouse\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
