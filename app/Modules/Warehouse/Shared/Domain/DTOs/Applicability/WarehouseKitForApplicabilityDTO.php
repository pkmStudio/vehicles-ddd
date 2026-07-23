<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Applicability;

use Spatie\LaravelData\Data;

final class WarehouseKitForApplicabilityDTO extends Data
{
    /**
     * @param  array<int, WarehouseNomenclatureForApplicabilityDTO>  $nomenclatures
     */
    public function __construct(
        public readonly int $id,
        public readonly int $typeId,
        public readonly int $quantityInPackage,
        public readonly bool $isActive,
        public readonly array $nomenclatures = [],
        public readonly ?WarehouseTypeForApplicabilityDTO $type = null,
    ) {}
}
