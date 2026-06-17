<?php

declare(strict_types=1);

namespace App\Vehicles\Validators;

use App\Vehicles\Enums\CarcaseTypeEnum;
use App\Vehicles\Enums\SteeringTypeEnum;
use App\Vehicles\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class VehicleValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'ms_id' => ['required', 'integer'],
            'mfa_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
            'type_carcase' => ['nullable', Rule::enum(CarcaseTypeEnum::class)],
            'steering_type' => ['nullable', Rule::enum(SteeringTypeEnum::class)],
            'generation' => ['nullable', 'string'],
            'generation_short' => ['nullable', 'string'],
            'localized_name' => ['nullable', 'string'],
            'excel_table_id' => ['nullable', 'string'],
            'provider' => ['nullable', 'string'],
            'generation_year_from' => ['nullable', 'integer'],
            'generation_year_to' => ['nullable', 'integer'],
            'is_allow' => ['nullable', 'boolean'],
        ])->validate();
    }
}
