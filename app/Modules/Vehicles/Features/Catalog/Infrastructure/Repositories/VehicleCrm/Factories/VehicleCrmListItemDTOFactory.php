<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;

final readonly class VehicleCrmListItemDTOFactory
{
    public function make(object $vehicle): VehicleCrmListItemDTO
    {
        return new VehicleCrmListItemDTO(
            id: (int) $vehicle->id,
            parentId: isset($vehicle->parent_id) ? (int) $vehicle->parent_id : null,
            parentMsId: isset($vehicle->parent_ms_id) ? (int) $vehicle->parent_ms_id : null,
            manufacturerId: (int) $vehicle->manufacturer_id,
            manufacturerName: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
            mfaId: (int) $vehicle->mfa_id,
            msId: (int) $vehicle->ms_id,
            name: (string) $vehicle->name,
            localizedName: isset($vehicle->localized_name) ? (string) $vehicle->localized_name : null,
            excelTableId: isset($vehicle->excel_table_id) ? (string) $vehicle->excel_table_id : null,
            generation: isset($vehicle->generation) ? (string) $vehicle->generation : null,
            generationShort: isset($vehicle->generation_short) ? (string) $vehicle->generation_short : null,
            generationYearFrom: isset($vehicle->generation_year_from) ? (int) $vehicle->generation_year_from : null,
            generationYearTo: isset($vehicle->generation_year_to) ? (int) $vehicle->generation_year_to : null,
            type: (string) $vehicle->type,
            typeCarcase: (string) $vehicle->type_carcase,
            provider: (string) $vehicle->provider,
            steeringType: (string) $vehicle->steering_type,
            isAllow: (bool) $vehicle->is_allow,
        );
    }
}
