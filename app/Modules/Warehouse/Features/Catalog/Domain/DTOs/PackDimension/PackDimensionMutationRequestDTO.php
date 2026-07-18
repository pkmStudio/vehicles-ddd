<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации упаковочного размера Warehouse.
 */
final readonly class PackDimensionMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreatePackDimensionRequestDTO|UpdatePackDimensionRequestDTO|DeletePackDimensionRequestDTO $request,
    ) {}
}
