<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;

final readonly class VehicleCrmFeatureOptionDTOFactory
{
    public function make(object $feature): VehicleCrmFeatureOptionDTO
    {
        return new VehicleCrmFeatureOptionDTO(
            id: (int) $feature->id,
            label: (string) $feature->name,
        );
    }
}
