<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;

final readonly class VehicleCrmFeatureValueOptionDTOFactory
{
    public function make(object $value): VehicleCrmFeatureValueOptionDTO
    {
        return new VehicleCrmFeatureValueOptionDTO(
            id: (int) $value->id,
            featureId: (int) $value->feature_id,
            label: (string) $value->name,
            shortCode: (string) $value->short_code,
        );
    }
}
