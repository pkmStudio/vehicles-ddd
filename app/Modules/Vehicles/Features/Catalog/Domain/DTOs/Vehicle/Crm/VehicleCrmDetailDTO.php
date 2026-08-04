<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use Illuminate\Support\Collection;

final readonly class VehicleCrmDetailDTO
{
    /**
     * @param  Collection<int, VehicleCrmModificationDTO>  $modifications
     * @param  Collection<int, VehicleCrmPartSpecificationDTO>  $partSpecifications
     */
    public function __construct(
        public VehicleCrmListItemDTO $vehicle,
        public Collection $modifications,
        public Collection $partSpecifications,
    ) {}
}
