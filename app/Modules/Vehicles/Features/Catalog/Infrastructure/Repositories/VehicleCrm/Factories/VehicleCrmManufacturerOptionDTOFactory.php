<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;

final readonly class VehicleCrmManufacturerOptionDTOFactory
{
    public function make(object $manufacturer): VehicleCrmManufacturerOptionDTO
    {
        return new VehicleCrmManufacturerOptionDTO(
            id: (int) $manufacturer->id,
            mfaId: (int) $manufacturer->mfa_id,
            label: (string) $manufacturer->name,
        );
    }
}
