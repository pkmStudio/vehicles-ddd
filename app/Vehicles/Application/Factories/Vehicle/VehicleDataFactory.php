<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Factories\Vehicle;

use App\Vehicles\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Domain\Enums\CarcaseTypeEnum;
use App\Vehicles\Domain\Enums\SteeringTypeEnum;
use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает VehicleData.
 * manufacturer_id/parent_id (резолв вызывающим) передаются тем же массивом.
 * Отсутствующие в строке поля берут дефолты (вызовы дают разные подмножества).
 */
final readonly class VehicleDataFactory
{
    /**
     * @throws ValidationException
     */
    public function make(array $row): VehicleData
    {
        $valid = Validator::make($row, [
            'ms_id' => ['required', 'integer'],
            'mfa_id' => ['required', 'integer'],
            'manufacturer_id' => ['required', 'integer'],
            'parent_id' => ['nullable', 'integer'],
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

        return new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: (int) $valid['manufacturer_id'],
            name: (string) $valid['name'],
            type: (string) $valid['type'],
            steeringType: $valid['steering_type'] ?? SteeringTypeEnum::LEFT->value,
            generation: $valid['generation'] ?? null,
            typeCarcase: $valid['type_carcase'] ?? null,
            generationYearFrom: isset($valid['generation_year_from']) ? (int) $valid['generation_year_from'] : null,
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            provider: $valid['provider'] ?? 'TD',
            parentId: isset($valid['parent_id']) ? (int) $valid['parent_id'] : null,
            excelTableId: $valid['excel_table_id'] ?? null,
            localizedName: $valid['localized_name'] ?? null,
            generationShort: $valid['generation_short'] ?? null,
            isAllow: (bool) ($valid['is_allow'] ?? false),
        );
    }
}
