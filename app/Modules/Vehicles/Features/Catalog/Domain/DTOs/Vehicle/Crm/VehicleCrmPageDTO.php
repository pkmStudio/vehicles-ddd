<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use Illuminate\Support\Collection;

final readonly class VehicleCrmPageDTO
{
    /**
     * @param  Collection<int, VehicleCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public VehicleCrmPaginationMetaDTO $meta,
    ) {}
}
