<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации спецификаций деталей.
 */
final readonly class PartSpecificationMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создает валидатор payload мутации спецификаций деталей.
     *
     * Шаги:
     * 1) Определить запрошенную операцию.
     * 2) Собрать базовые и операционные правила.
     * 3) Вернуть Laravel Validator для Handler.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(array $data): Validator
    {
        $operation = (string) ($data['operation'] ?? '');
        $ownerType = (string) ($data['part_specification']['owner']['type'] ?? '');

        $rules = [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'operation' => ['required', 'string', Rule::in($this->operations())],
            'part_specification' => ['required', 'array'],
            'part_specification.id' => ['required', 'integer', 'min:1'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value
            || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += $this->specificationRules();
        }

        if (($operation === CatalogMutationOperationEnum::Create->value
                || $operation === CatalogMutationOperationEnum::Update->value)
            && $ownerType === PartableTypeEnum::VEHICLE->value) {
            $rules += $this->vehicleOwnerRules();
        }

        if (($operation === CatalogMutationOperationEnum::Create->value
                || $operation === CatalogMutationOperationEnum::Update->value)
            && $ownerType === PartableTypeEnum::ENGINE->value) {
            $rules += $this->engineOwnerRules();
        }

        return $this->validator->make(
            data: $data,
            rules: $rules,
        );
    }

    /**
     * Возвращает правила общих полей спеки.
     *
     * @return array<string, array<int, mixed>>
     */
    private function specificationRules(): array
    {
        return [
            'part_specification.owner' => ['required', 'array'],
            'part_specification.owner.type' => ['required', 'string', Rule::in($this->enumValues(PartableTypeEnum::cases()))],
            'part_specification.owner.external_id' => ['required', 'integer', 'min:1'],
            'part_specification.template' => ['required', 'string', Rule::in($this->enumValues(DetailTemplateEnum::cases()))],
            'part_specification.details' => ['required', 'array'],
            'part_specification.feature_value_id' => ['nullable', 'integer', 'min:1'],
            'part_specification.name' => ['nullable', 'string', 'max:255'],
            'part_specification.text' => ['nullable', 'string'],
        ];
    }

    /**
     * Возвращает правила payload автомобиля-владельца.
     *
     * @return array<string, array<int, mixed>>
     */
    private function vehicleOwnerRules(): array
    {
        return [
            'part_specification.owner.vehicle' => ['nullable', 'array'],
            'part_specification.owner.vehicle.mfa_id' => ['required_with:part_specification.owner.vehicle', 'integer'],
            'part_specification.owner.vehicle.parent_ms_id' => ['nullable', 'integer'],
            'part_specification.owner.vehicle.name' => ['required_with:part_specification.owner.vehicle', 'string', 'max:255'],
            'part_specification.owner.vehicle.localized_name' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.vehicle.excel_table_id' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.vehicle.generation' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.vehicle.generation_short' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.vehicle.generation_year_from' => ['nullable', 'integer', 'min:1900', 'max:2155'],
            'part_specification.owner.vehicle.generation_year_to' => ['nullable', 'integer', 'min:1900', 'max:2155'],
            'part_specification.owner.vehicle.type' => ['required_with:part_specification.owner.vehicle', 'string', Rule::in($this->enumValues(VehicleTypeEnum::cases()))],
            'part_specification.owner.vehicle.type_carcase' => ['required_with:part_specification.owner.vehicle', 'string', Rule::in($this->enumValues(CarcaseTypeEnum::cases()))],
            'part_specification.owner.vehicle.provider' => ['nullable', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
            'part_specification.owner.vehicle.steering_type' => ['nullable', 'string', Rule::in($this->enumValues(SteeringTypeEnum::cases()))],
            'part_specification.owner.vehicle.is_allow' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Возвращает правила payload двигателя-владельца.
     *
     * @return array<string, array<int, mixed>>
     */
    private function engineOwnerRules(): array
    {
        return [
            'part_specification.owner.engine' => ['nullable', 'array'],
            'part_specification.owner.engine.code_engine' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.engine.eng_power_kw_start' => ['nullable', 'integer'],
            'part_specification.owner.engine.eng_power_kw_upto' => ['nullable', 'integer'],
            'part_specification.owner.engine.eng_power_ps_start' => ['nullable', 'integer'],
            'part_specification.owner.engine.eng_power_ps_upto' => ['nullable', 'integer'],
            'part_specification.owner.engine.engine_capacity' => ['nullable', 'string', 'max:255'],
            'part_specification.owner.engine.cylinder_diameter' => ['nullable', 'numeric'],
            'part_specification.owner.engine.cylinder_count' => ['nullable', 'integer'],
            'part_specification.owner.engine.eng_number_of_valves' => ['nullable', 'integer'],
            'part_specification.owner.engine.eng_fuel_type' => ['nullable', 'string', Rule::in($this->enumValues(EngineFuelTypeEnum::cases()))],
            'part_specification.owner.engine.group_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Возвращает список строковых значений поддерживаемых операций.
     *
     * @return list<string>
     */
    private function operations(): array
    {
        return array_map(
            fn (CatalogMutationOperationEnum $operation): string => $operation->value,
            CatalogMutationOperationEnum::cases(),
        );
    }

    /**
     * Возвращает строковые значения enum cases для правил валидации.
     *
     * @param  array<int, object>  $cases
     * @return list<string>
     */
    private function enumValues(array $cases): array
    {
        return array_map(
            fn (object $case): string => $case->value,
            $cases,
        );
    }
}
