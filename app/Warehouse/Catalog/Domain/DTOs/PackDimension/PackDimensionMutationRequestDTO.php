<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\PackDimension;

use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

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
