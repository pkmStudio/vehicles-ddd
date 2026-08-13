<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use Illuminate\Support\Collection;

final readonly class VehicleCrmRelationPageDTO
{
    /**
     * @param  Collection<int, object>  $data
     */
    public function __construct(
        public Collection $data,
        public VehicleCrmPaginationMetaDTO $meta,
    ) {}
}
