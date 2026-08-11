<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use Illuminate\Support\Collection;

/**
 * Маппит SQL projection модификации в CRM DTO.
 */
final readonly class VehicleCrmModificationDTOFactory
{
    /**
     * Создает DTO модификации с вложенными двигателями.
     *
     * Шаги:
     * 1. Принимает SQL projection модификации.
     * 2. Приводит scalar поля projection к типам DTO.
     * 3. Добавляет collection связанных двигателей.
     *
     * @param  Collection<int, mixed>  $engines
     */
    public function make(object $modification, Collection $engines): VehicleCrmModificationDTO
    {
        return new VehicleCrmModificationDTO(
            id: (int) $modification->id,
            vehicleId: (int) $modification->vehicle_id,
            msId: (int) $modification->ms_id,
            modId: (int) $modification->mod_id,
            yearFrom: isset($modification->year_from) ? (int) $modification->year_from : null,
            yearTo: isset($modification->year_to) ? (int) $modification->year_to : null,
            description: isset($modification->description) ? (string) $modification->description : null,
            type: (string) $modification->type,
            brakeSystemType: isset($modification->brake_system_type) ? (string) $modification->brake_system_type : null,
            powerPs: isset($modification->power_ps) ? (int) $modification->power_ps : null,
            powerKw: isset($modification->power_kw) ? (int) $modification->power_kw : null,
            engineType: isset($modification->engine_type) ? (string) $modification->engine_type : null,
            gearType: isset($modification->gear_type) ? (string) $modification->gear_type : null,
            driveType: isset($modification->drive_type) ? (string) $modification->drive_type : null,
            localizedName: isset($modification->localized_name) ? (string) $modification->localized_name : null,
            numberOfCylinders: isset($modification->number_of_cylinders) ? (int) $modification->number_of_cylinders : null,
            capacityLt: isset($modification->capacity_lt) ? (float) $modification->capacity_lt : null,
            engines: $engines,
        );
    }
}
