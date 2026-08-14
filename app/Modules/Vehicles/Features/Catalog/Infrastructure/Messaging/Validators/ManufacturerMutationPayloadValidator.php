<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор сообщения мутации производителей.
 */
final readonly class ManufacturerMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * - Сохранить фабрику Laravel-валидаторов для сборки правил сообщения.
     */
    public function __construct(private ValidatorFactory $validator) {}

    /**
     * Создаёт валидатор сообщения мутации производителя.
     *
     * Шаги:
     * - Определить операцию из входящих данных.
     * - Собрать базовые правила пользователя, operation id, операции и производителя.
     * - Добавить правила имени и provider для создания и обновления.
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
            'manufacturer' => ['required', 'array'],
            'manufacturer.mfa_id' => ['required', 'integer'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'manufacturer.name' => ['required', 'string', 'max:255'],
                'manufacturer.provider' => ['required', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
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
     */
    private function enumValues(array $cases): array
    {
        $toEnumValue = fn (object $case): string => $case->value;

        return array_map($toEnumValue, $cases);
    }
}
