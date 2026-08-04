<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmEngineDTO;

final readonly class VehicleCrmEngineDTOFactory
{
    public function make(object $engine): VehicleCrmEngineDTO
    {
        return new VehicleCrmEngineDTO(
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: isset($engine->code_engine) ? (string) $engine->code_engine : null,
            engineCapacity: isset($engine->engine_capacity) ? (string) $engine->engine_capacity : null,
            cylinderCount: isset($engine->cylinder_count) ? (int) $engine->cylinder_count : null,
            cylinderDiameter: isset($engine->cylinder_diameter) ? (float) $engine->cylinder_diameter : null,
            engPowerKwStart: isset($engine->eng_power_kw_start) ? (int) $engine->eng_power_kw_start : null,
            engPowerKwUpto: isset($engine->eng_power_kw_upto) ? (int) $engine->eng_power_kw_upto : null,
            engPowerPsStart: isset($engine->eng_power_ps_start) ? (int) $engine->eng_power_ps_start : null,
            engPowerPsUpto: isset($engine->eng_power_ps_upto) ? (int) $engine->eng_power_ps_upto : null,
            engNumberOfValves: isset($engine->eng_number_of_valves) ? (int) $engine->eng_number_of_valves : null,
            engFuelType: isset($engine->eng_fuel_type) ? (string) $engine->eng_fuel_type : null,
            groupId: isset($engine->group_id) ? (int) $engine->group_id : null,
        );
    }
}
