<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Nomenclature;

use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-номенклатуры.
 */
final readonly class NomenclatureMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateNomenclatureRequestDTO|UpdateNomenclatureRequestDTO|DeleteNomenclatureRequestDTO $request,
    ) {}
}
