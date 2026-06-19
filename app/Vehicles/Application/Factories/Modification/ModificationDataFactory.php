<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Factories\Modification;

use App\Vehicles\Application\ModelData\Modification\ModificationData;
use App\Vehicles\Domain\Enums\BrakeSystemTypeEnum;
use App\Vehicles\Domain\Enums\DriveTypeEnum;
use App\Vehicles\Domain\Enums\EngineTypeEnum;
use App\Vehicles\Domain\Enums\GearTypeEnum;
use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает ModificationData.
 * vehicle_id (резолв ТС вызывающим) передаётся тем же массивом.
 */
final readonly class ModificationDataFactory
{
    /**
     * @throws ValidationException
     */
    public function make(array $row): ModificationData
    {
        $valid = Validator::make($row, [
            'mod_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
            'vehicle_id' => ['required', 'integer'],
            'ms_id' => ['required', 'integer'],
            'year_from' => ['nullable', 'integer'],
            'year_to' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'power_ps' => ['nullable', 'integer'],
            'power_kw' => ['nullable', 'integer'],
            'engine_type' => ['nullable', Rule::enum(EngineTypeEnum::class)],
            'gear_type' => ['nullable', Rule::enum(GearTypeEnum::class)],
            'drive_type' => ['nullable', Rule::enum(DriveTypeEnum::class)],
            'brake_system_type' => ['nullable', Rule::enum(BrakeSystemTypeEnum::class)],
            'number_of_cylinders' => ['nullable', 'integer'],
            'capacity_lt' => ['nullable', 'numeric'],
        ])->validate();

        return new ModificationData(
            modId: (int) $valid['mod_id'],
            type: (string) $valid['type'],
            vehicleId: (int) $valid['vehicle_id'],
            msId: (int) $valid['ms_id'],
            yearFrom: isset($valid['year_from']) ? (int) $valid['year_from'] : null,
            yearTo: isset($valid['year_to']) ? (int) $valid['year_to'] : null,
            description: $valid['description'] ?? null,
            powerPs: isset($valid['power_ps']) ? (int) $valid['power_ps'] : null,
            powerKw: isset($valid['power_kw']) ? (int) $valid['power_kw'] : null,
            engineType: $valid['engine_type'] ?? null,
            gearType: $valid['gear_type'] ?? null,
            driveType: $valid['drive_type'] ?? null,
            brakeSystemType: $valid['brake_system_type'] ?? null,
            numberOfCylinders: isset($valid['number_of_cylinders']) ? (int) $valid['number_of_cylinders'] : null,
            capacityLt: isset($valid['capacity_lt']) ? (float) $valid['capacity_lt'] : null,
        );
    }
}
