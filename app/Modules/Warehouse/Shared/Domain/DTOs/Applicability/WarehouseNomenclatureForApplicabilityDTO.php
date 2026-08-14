<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Applicability;

use Spatie\LaravelData\Data;

final class WarehouseNomenclatureForApplicabilityDTO extends Data
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $id,
        public readonly int $typeId,
        public readonly int $quantityInPak,
        public readonly array $details,
        public readonly WarehouseTypeForApplicabilityDTO $type,
        public readonly int $sort = 0,
    ) {}
}
