<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-validator данные сообщения мутации Warehouse-бренда.
 */
final readonly class BrandMutationPayloadValidator
{
    /**
     * Получает Laravel validator factory для brand mutation payload.
     *
     * Шаги:
     * 1) Принять ValidatorFactory из Laravel container.
     * 2) Использовать factory при сборке validator для конкретного payload.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта Brand CRUD.
     *
     * @param  array<string, mixed>  $data
     *
     * Шаги:
     * 1) Прочитать operation из входящего Rabbit payload.
     * 2) Собрать базовые правила user_id, operation_id, operation и brand.
     * 3) Добавить правила create/update/delete для brand fields.
     * 4) Вернуть validator вызывающему handler.
     */
    public function make(array $data): Validator
    {
        $operation = (string) ($data['operation'] ?? '');

        $rules = [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'operation' => ['required', 'string', Rule::in($this->operations())],
            'brand' => ['required', 'array'],
        ];

        if ($operation === WarehouseCatalogMutationOperationEnum::Update->value
            || $operation === WarehouseCatalogMutationOperationEnum::Delete->value) {
            $rules['brand.id'] = ['required', 'integer', 'min:1'];
        }

        if ($operation === WarehouseCatalogMutationOperationEnum::Create->value
            || $operation === WarehouseCatalogMutationOperationEnum::Update->value) {
            $rules += [
                'brand.name' => ['required', 'string', 'max:255'],
                'brand.number_sert' => ['required', 'string', 'max:255'],
                'brand.date_start' => ['required', 'date'],
                'brand.date_end' => ['required', 'date'],
                'brand.char' => ['required', 'string', 'max:1'],
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
        $toOperationValue = fn (WarehouseCatalogMutationOperationEnum $operation): string => $operation->value;

        return array_map(
            $toOperationValue,
            WarehouseCatalogMutationOperationEnum::cases(),
        );
    }
}
