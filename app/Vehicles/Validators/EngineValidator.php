<?php

declare(strict_types=1);

namespace App\Vehicles\Validators;

use App\Vehicles\Enums\EngineFuelTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class EngineValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'eng_id' => ['required', 'integer'],
            'code_engine' => ['nullable', 'string'],
            'eng_power_kw_start' => ['nullable', 'integer'],
            'eng_power_kw_upto' => ['nullable', 'integer'],
            'eng_power_ps_start' => ['nullable', 'integer'],
            'eng_power_ps_upto' => ['nullable', 'integer'],
            'engine_capacity' => ['nullable', 'string'],
            'cylinder_diameter' => ['nullable', 'numeric'],
            'cylinder_count' => ['nullable', 'integer'],
            'eng_number_of_valves' => ['nullable', 'integer'],
            'eng_fuel_type' => ['nullable', Rule::enum(EngineFuelTypeEnum::class)],
        ])->validate();
    }
}
