<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Factories;

use App\Vehicles\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Vehicles\Import\Domain\ModelData\ModificationData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает ModificationData.
 * vehicle_id (резолв ТС вызывающим) передаётся тем же массивом.
 */
final readonly class ModificationDataFactory implements ModificationDataFactoryInterface
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
            'description' => ['nullable'],
            'power_ps' => ['nullable', 'integer'],
            'power_kw' => ['nullable', 'integer'],
            'engine_type' => ['nullable', Rule::enum(EngineTypeEnum::class)],
            'gear_type' => ['nullable', Rule::enum(GearTypeEnum::class)],
            'drive_type' => ['nullable', Rule::enum(DriveTypeEnum::class)],
            'brake_system_type' => ['nullable', Rule::enum(BrakeSystemTypeEnum::class)],
            'number_of_cylinders' => ['nullable', 'integer'],
            'capacity_lt' => ['nullable', 'numeric'],
        ])->validate();

        $type = VehicleTypeEnum::from($valid['type']);
        $yearFrom = isset($valid['year_from']) ? (int) $valid['year_from'] : null;
        $yearTo = isset($valid['year_to']) ? (int) $valid['year_to'] : null;
        $description = isset($valid['description']) ? (string) $valid['description'] : null;
        $powerPs = isset($valid['power_ps']) ? (int) $valid['power_ps'] : null;
        $powerKw = isset($valid['power_kw']) ? (int) $valid['power_kw'] : null;
        $engineType = isset($valid['engine_type']) ? EngineTypeEnum::from($valid['engine_type']) : null;
        $gearType = isset($valid['gear_type']) ? GearTypeEnum::from($valid['gear_type']) : null;
        $driveType = isset($valid['drive_type']) ? DriveTypeEnum::from($valid['drive_type']) : null;
        $brakeSystemType = isset($valid['brake_system_type']) ? BrakeSystemTypeEnum::from($valid['brake_system_type']) : null;
        $numberOfCylinders = isset($valid['number_of_cylinders']) ? (int) $valid['number_of_cylinders'] : null;
        $capacityLt = isset($valid['capacity_lt']) ? (float) $valid['capacity_lt'] : null;

        return new ModificationData(
            modId: (int) $valid['mod_id'],
            type: $type,
            vehicleId: (int) $valid['vehicle_id'],
            msId: (int) $valid['ms_id'],
            yearFrom: $yearFrom,
            yearTo: $yearTo,
            description: $description,
            powerPs: $powerPs,
            powerKw: $powerKw,
            engineType: $engineType,
            gearType: $gearType,
            driveType: $driveType,
            brakeSystemType: $brakeSystemType,
            numberOfCylinders: $numberOfCylinders,
            capacityLt: $capacityLt,
        );
    }
}
