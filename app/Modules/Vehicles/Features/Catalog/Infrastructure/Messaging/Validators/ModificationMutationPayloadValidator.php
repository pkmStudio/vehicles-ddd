<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации модификаций.
 */
final readonly class ModificationMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(private ValidatorFactory $validator) {}

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
            'modification' => ['required', 'array'],
            'modification.mod_id' => ['required', 'integer'],
            'modification.type' => ['required', 'string', Rule::in($this->enumValues(VehicleTypeEnum::cases()))],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'modification.ms_id' => ['required', 'integer'],
                'modification.year_from' => ['nullable', 'integer', 'min:1900', 'max:2155'],
                'modification.year_to' => ['nullable', 'integer', 'min:1900', 'max:2155'],
                'modification.description' => ['nullable', 'string', 'max:255'],
                'modification.power_ps' => ['nullable', 'integer'],
                'modification.power_kw' => ['nullable', 'integer'],
                'modification.engine_type' => ['nullable', 'string', Rule::in($this->enumValues(EngineTypeEnum::cases()))],
                'modification.gear_type' => ['nullable', 'string', Rule::in($this->enumValues(GearTypeEnum::cases()))],
                'modification.drive_type' => ['nullable', 'string', Rule::in($this->enumValues(DriveTypeEnum::cases()))],
                'modification.brake_system_type' => ['nullable', 'string', Rule::in($this->enumValues(BrakeSystemTypeEnum::cases()))],
                'modification.number_of_cylinders' => ['nullable', 'integer'],
                'modification.capacity_lt' => ['nullable', 'numeric'],
            ];
        }

        return $this->validator->make(
            data: $data,
            rules: $rules,
        );
    }

    /**
     * Возвращает список строковых значений поддерживаемых операций.
     */
    private function operations(): array
    {
        return array_map(fn (CatalogMutationOperationEnum $operation): string => $operation->value, CatalogMutationOperationEnum::cases());
    }

    /**
     * Возвращает строковые значения enum cases для правил валидации.
     */
    private function enumValues(array $cases): array
    {
        return array_map(fn (object $case): string => $case->value, $cases);
    }
}
