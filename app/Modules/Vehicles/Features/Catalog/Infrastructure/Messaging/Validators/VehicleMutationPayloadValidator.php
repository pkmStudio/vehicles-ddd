<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации автомобилей.
 */
final readonly class VehicleMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        $operation = (string) ($data['operation'] ?? '');

        $rules = [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'operation' => ['required', 'string', Rule::in($this->operations())],
            'vehicle' => ['required', 'array'],
            'vehicle.ms_id' => ['required', 'integer'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value
            || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'vehicle.mfa_id' => ['required', 'integer'],
                'vehicle.parent_ms_id' => ['nullable', 'integer'],
                'vehicle.name' => ['required', 'string', 'max:255'],
                'vehicle.localized_name' => ['nullable', 'string', 'max:255'],
                'vehicle.excel_table_id' => ['nullable', 'string', 'max:255'],
                'vehicle.generation' => ['nullable', 'string', 'max:255'],
                'vehicle.generation_short' => ['nullable', 'string', 'max:255'],
                'vehicle.generation_year_from' => ['nullable', 'integer', 'min:1900', 'max:2155'],
                'vehicle.generation_year_to' => ['nullable', 'integer', 'min:1900', 'max:2155'],
                'vehicle.type' => ['required', 'string', Rule::in($this->enumValues(VehicleTypeEnum::cases()))],
                'vehicle.type_carcase' => ['required', 'string', Rule::in($this->enumValues(CarcaseTypeEnum::cases()))],
                'vehicle.provider' => ['nullable', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
                'vehicle.steering_type' => ['nullable', 'string', Rule::in($this->enumValues(SteeringTypeEnum::cases()))],
                'vehicle.is_allow' => ['nullable', 'boolean'],
            ];
        }

        return $this->validator->make(
            data: $data,
            rules: $rules,
        );
    }

    /**
     * @return list<string>
     */
    private function operations(): array
    {
        $toOperationValue = fn (CatalogMutationOperationEnum $operation): string => $operation->value;

        return array_map(
            $toOperationValue,
            CatalogMutationOperationEnum::cases(),
        );
    }

    /**
     * @param  array<int, object>  $cases
     * @return list<string>
     */
    private function enumValues(array $cases): array
    {
        $toEnumValue = fn (object $case): string => $case->value;

        return array_map(
            $toEnumValue,
            $cases,
        );
    }
}
