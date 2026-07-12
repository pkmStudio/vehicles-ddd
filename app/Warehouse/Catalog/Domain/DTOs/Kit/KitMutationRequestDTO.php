<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Kit;

use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-набора.
 */
final readonly class KitMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateKitRequestDTO|UpdateKitRequestDTO|DeleteKitRequestDTO $request,
    ) {}
}
