<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use BackedEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор сообщения мутации двигателей.
 */
final readonly class EngineMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * - Сохранить фабрику Laravel-валидаторов для сборки правил сообщения.
     */
    public function __construct(private ValidatorFactory $validator) {}

    /**
     * Создаёт валидатор сообщения мутации двигателя.
     *
     * Шаги:
     * - Определить операцию из входящих данных.
     * - Собрать базовые правила пользователя, operation id, операции и двигателя.
     * - Добавить атрибуты двигателя только для создания и обновления.
     *
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
            'engine.eng_id' => [$operation === CatalogMutationOperationEnum::Create->value ? 'prohibited' : 'required', 'integer'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'engine.code_engine' => ['required', 'string', 'max:255'],
                'engine.engine_capacity' => ['nullable', 'numeric', 'min:0'],
                'engine.cylinder_count' => ['nullable', 'integer', 'min:0'],
                'engine.cylinder_diameter' => ['nullable', 'numeric', 'min:0'],
                'engine.power_kw_start' => ['required', 'integer', 'min:0'],
                'engine.power_kw_upto' => ['nullable', 'integer', 'min:0'],
                'engine.power_ps_start' => ['required', 'integer', 'min:0'],
                'engine.power_ps_upto' => ['nullable', 'integer', 'min:0'],
                'engine.number_of_valves' => ['nullable', 'integer', 'min:0'],
                'engine.fuel_type' => ['required', 'string', Rule::in($this->enumValues(EngineFuelTypeEnum::cases()))],
                'engine.group_id' => ['nullable', 'integer', 'min:0'],
                'engine.provider' => ['required', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
                'engine.allow_change_fields' => ['present', 'array'],
                'engine.allow_change_fields.*' => ['string', 'max:64'],
            ];
        }

        return $this->validator->make(
            data: $data,
            rules: $rules,
        );
    }

    /**
     * Возвращает список строковых значений поддерживаемых операций.
     *
     * Шаги:
     * - Пройти по cases enum операций мутации.
     * - Вернуть их строковые значения для Rule::in().
     */
    private function operations(): array
    {
        $toOperationValue = fn (CatalogMutationOperationEnum $operation): string => $operation->value;

        return array_map($toOperationValue, CatalogMutationOperationEnum::cases());
    }

    /**
     * Возвращает строковые значения enum cases для правил валидации.
     *
     * Шаги:
     * - Пройти по cases переданного enum.
     * - Вернуть значения cases для Rule::in().
     *
     * @param  array<int, BackedEnum>  $cases
     * @return list<string>
     */
    private function enumValues(array $cases): array
    {
        $toEnumValue = fn (BackedEnum $case): string => (string) $case->value;

        return array_map($toEnumValue, $cases);
    }
}
