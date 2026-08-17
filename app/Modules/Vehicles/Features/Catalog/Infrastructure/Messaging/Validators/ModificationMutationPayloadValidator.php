<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use BackedEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор сообщения мутации модификаций.
 */
final readonly class ModificationMutationPayloadValidator
{
    /**
     * Инициализирует зависимости класса через контейнер.
     *
     * Шаги:
     * - Сохранить фабрику Laravel-валидаторов для сборки правил сообщения.
     */
    public function __construct(private ValidatorFactory $validator) {}

    /**
     * Создаёт валидатор сообщения мутации модификации.
     *
     * Шаги:
     * - Определить операцию из входящих данных.
     * - Собрать базовые правила пользователя, operation id, операции и модификации.
     * - Добавить поля модификации и вложенных двигателей только для создания и обновления.
     *
     * @param  array{
     *     user_id?: int|string,
     *     operation_id?: string,
     *     operation?: string,
     *     modification?: array{
     *         mod_id?: int|string|null,
     *         ms_id?: int|string,
     *         type?: string,
     *         year_from?: int|string,
     *         year_to?: int|string|null,
     *         description?: string,
     *         description_short?: string|null,
     *         power_ps?: int|string,
     *         power_kw?: int|string,
     *         engine_type?: string,
     *         gear_type?: string|null,
     *         drive_type?: string|null,
     *         brake_system_type?: string|null,
     *         number_of_cylinders?: int|string|null,
     *         capacity_lt?: int|float|string|null,
     *         localized_name?: string|null,
     *         provider?: string,
     *         allow_change_fields?: array<int, string>,
     *         engines?: list<array{eng_id?: int|string, relation_provider?: string}>
     *     }
     * } $data
     */
    public function make(array $data): Validator
    {
        $operation = (string) ($data['operation'] ?? '');

        $rules = [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'operation' => ['required', 'string', Rule::in($this->operations())],
            'modification' => ['required', 'array'],
            'modification.mod_id' => [$operation === CatalogMutationOperationEnum::Create->value ? 'nullable' : 'required', 'integer'],
            'modification.type' => ['required', 'string', Rule::in($this->enumValues(VehicleTypeEnum::cases()))],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'modification.ms_id' => ['required', 'integer'],
                'modification.year_from' => ['required', 'integer', 'min:1900', 'max:2155'],
                'modification.year_to' => ['nullable', 'integer', 'min:1900', 'max:2155'],
                'modification.description' => ['required', 'string', 'max:255'],
                'modification.description_short' => ['nullable', 'string', 'max:255'],
                'modification.power_ps' => ['required', 'integer'],
                'modification.power_kw' => ['required', 'integer'],
                'modification.engine_type' => ['required', 'string', Rule::in($this->enumValues(EngineTypeEnum::cases()))],
                'modification.gear_type' => ['nullable', 'string', Rule::in($this->enumValues(GearTypeEnum::cases()))],
                'modification.drive_type' => ['nullable', 'string', Rule::in($this->enumValues(DriveTypeEnum::cases()))],
                'modification.brake_system_type' => ['nullable', 'string', Rule::in($this->enumValues(BrakeSystemTypeEnum::cases()))],
                'modification.number_of_cylinders' => ['nullable', 'integer'],
                'modification.capacity_lt' => ['nullable', 'numeric'],
                'modification.localized_name' => ['nullable', 'string', 'max:255'],
                'modification.provider' => ['required', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
                'modification.allow_change_fields' => ['present', 'array'],
                'modification.allow_change_fields.*' => ['string', 'max:64'],
                'modification.engines' => ['nullable', 'array'],
                'modification.engines.*' => ['array'],
                'modification.engines.*.eng_id' => ['required', 'integer'],
                'modification.engines.*.relation_provider' => ['required', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
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
