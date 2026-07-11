<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Factories;

use App\Vehicles\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Валидирует сырую строку и собирает VehicleData.
 * manufacturer_id/parent_id (резолв вызывающим) передаются тем же массивом.
 * Отсутствующие в строке поля берут дефолты (вызовы дают разные подмножества).
 */
final readonly class VehicleDataFactory implements VehicleDataFactoryInterface
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
            'name' => ['required'],
            'type' => ['required', Rule::enum(VehicleTypeEnum::class)],
            'type_carcase' => ['required', Rule::enum(CarcaseTypeEnum::class)],
            'steering_type' => ['nullable', Rule::enum(SteeringTypeEnum::class)],
            'generation' => ['nullable'],
            'generation_short' => ['nullable'],
            'localized_name' => ['nullable'],
            'excel_table_id' => ['nullable'],
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
            'generation_year_from' => ['nullable', 'integer'],
            'generation_year_to' => ['nullable', 'integer'],
            'is_allow' => ['nullable', 'boolean'],
        ])->validate();

        $type = VehicleTypeEnum::from($valid['type']);

        return new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: (int) $valid['manufacturer_id'],
            name: (string) $valid['name'],
            type: $type,
            steeringType: isset($valid['steering_type']) ? SteeringTypeEnum::from($valid['steering_type']) : SteeringTypeEnum::LEFT,
            generation: isset($valid['generation']) ? (string) $valid['generation'] : null,
            typeCarcase: CarcaseTypeEnum::from($valid['type_carcase']),
            generationYearFrom: isset($valid['generation_year_from']) ? (int) $valid['generation_year_from'] : null,
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            provider: ProviderEnum::from($valid['provider']),
            parentId: isset($valid['parent_id']) ? (int) $valid['parent_id'] : null,
            excelTableId: isset($valid['excel_table_id']) ? (string) $valid['excel_table_id'] : null,
            localizedName: isset($valid['localized_name']) ? (string) $valid['localized_name'] : null,
            generationShort: isset($valid['generation_short']) ? (string) $valid['generation_short'] : null,
            isAllow: (bool) ($valid['is_allow'] ?? false),
        );
    }
}
