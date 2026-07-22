<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Laravel Validator-адаптер, который валидирует строку и собирает VehicleData.
 */
final readonly class VehicleDataFactory implements VehicleDataFactoryInterface
{
    public function make(array $row): VehicleData
    {
        try {
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
                'id' => ['nullable', 'integer'],
            ])->validate();
        } catch (ValidationException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }

        return new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: (int) $valid['manufacturer_id'],
            name: (string) $valid['name'],
            type: VehicleTypeEnum::from($valid['type']),
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
            id: isset($valid['id']) ? (int) $valid['id'] : null,
        );
    }
}
