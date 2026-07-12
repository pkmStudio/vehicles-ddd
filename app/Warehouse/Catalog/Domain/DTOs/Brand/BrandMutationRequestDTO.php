<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Brand;

use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-бренда.
 */
final readonly class BrandMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateBrandRequestDTO|UpdateBrandRequestDTO|DeleteBrandRequestDTO $request,
    ) {}
}
