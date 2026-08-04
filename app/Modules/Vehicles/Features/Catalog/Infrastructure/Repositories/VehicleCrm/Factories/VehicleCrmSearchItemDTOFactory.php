<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;

final readonly class VehicleCrmSearchItemDTOFactory
{
    public function make(object $vehicle): VehicleCrmSearchItemDTO
    {
        return new VehicleCrmSearchItemDTO(
            id: (int) $vehicle->id,
            label: $this->label($vehicle),
            msId: (int) $vehicle->ms_id,
            manufacturer: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
        );
    }

    private function label(object $vehicle): string
    {
        return sprintf(
            '%s | %s %s %s | %s (%s-%s)',
            $vehicle->ms_id,
            $vehicle->manufacturer_name,
            $vehicle->name,
            $vehicle->generation,
            $vehicle->localized_name ?: '',
            $vehicle->generation_year_from,
            $vehicle->generation_year_to ?: 'н.в.',
        );
    }
}
