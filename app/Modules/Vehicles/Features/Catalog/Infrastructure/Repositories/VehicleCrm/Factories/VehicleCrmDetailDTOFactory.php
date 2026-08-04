<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCrmDetailDTOFactory
{
    /**
     * @param  Collection<int, mixed>  $modifications
     * @param  Collection<int, mixed>  $partSpecifications
     */
    public function make(
        VehicleCrmListItemDTO $vehicle,
        Collection $modifications,
        Collection $partSpecifications,
    ): VehicleCrmDetailDTO {
        return new VehicleCrmDetailDTO(
            vehicle: $vehicle,
            modifications: $modifications,
            partSpecifications: $partSpecifications,
        );
    }
}
