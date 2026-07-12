<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Messaging\Validators;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации двигателей.
 */
final readonly class EngineMutationPayloadValidator
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
            'engine' => ['required', 'array'],
            'engine.eng_id' => ['required', 'integer'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'engine.code_engine' => ['nullable', 'string', 'max:255'],
                'engine.engine_capacity' => ['nullable', 'string', 'max:255'],
                'engine.cylinder_count' => ['nullable', 'integer'],
                'engine.cylinder_diameter' => ['nullable', 'numeric'],
                'engine.eng_power_kw_start' => ['nullable', 'integer'],
                'engine.eng_power_kw_upto' => ['nullable', 'integer'],
                'engine.eng_power_ps_start' => ['nullable', 'integer'],
                'engine.eng_power_ps_upto' => ['nullable', 'integer'],
                'engine.eng_number_of_valves' => ['nullable', 'integer'],
                'engine.eng_fuel_type' => ['nullable', 'string', Rule::in($this->enumValues(EngineFuelTypeEnum::cases()))],
                'engine.group_id' => ['nullable', 'integer'],
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
