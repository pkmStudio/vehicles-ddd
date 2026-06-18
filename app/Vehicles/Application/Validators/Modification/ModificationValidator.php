<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Validators\Modification;

use App\Vehicles\Domain\Enums\BrakeSystemTypeEnum;
use App\Vehicles\Domain\Enums\DriveTypeEnum;
use App\Vehicles\Domain\Enums\EngineTypeEnum;
use App\Vehicles\Domain\Enums\GearTypeEnum;
use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ModificationValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'mod_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
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
    }
}
