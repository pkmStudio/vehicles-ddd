<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-validator данные сообщения мутации упаковочного размера Warehouse.
 */
final readonly class PackDimensionMutationPayloadValidator
{
    /**
     * Получает Laravel validator factory для pack dimension mutation payload.
     *
     * Шаги:
     * 1) Принять ValidatorFactory из Laravel container.
     * 2) Использовать factory при сборке validator для конкретного payload.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта PackDimension CRUD.
     *
     * @param  array<string, mixed>  $data
     *
     * Шаги:
     * 1) Прочитать operation из входящего Rabbit payload.
     * 2) Собрать базовые правила user_id, operation_id, operation и pack_dimension.
     * 3) Добавить правила create/update/delete для полей упаковочного размера.
     * 4) Вернуть validator вызывающему handler.
     */
    public function make(array $data): Validator
    {
        $operation = (string) ($data['operation'] ?? '');

        $rules = [
            'user_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'string', 'max:128'],
            'operation' => ['required', 'string', Rule::in($this->operations())],
            'pack_dimension' => ['required', 'array'],
        ];

        if ($operation === WarehouseCatalogMutationOperationEnum::Update->value
            || $operation === WarehouseCatalogMutationOperationEnum::Delete->value) {
            $rules['pack_dimension.id'] = ['required', 'integer', 'min:1'];
        }

        if ($operation === WarehouseCatalogMutationOperationEnum::Create->value
            || $operation === WarehouseCatalogMutationOperationEnum::Update->value) {
            $rules += [
                'pack_dimension.name' => ['required', 'string', 'max:255'],
                'pack_dimension.weight' => ['required', 'integer', 'min:1'],
                'pack_dimension.width' => ['required', 'integer', 'min:1'],
                'pack_dimension.height' => ['required', 'integer', 'min:1'],
                'pack_dimension.length' => ['required', 'integer', 'min:1'],
                'pack_dimension.price' => ['required', 'integer', 'min:0'],
                'pack_dimension.type_id' => ['required', 'integer', 'min:1'],
                'pack_dimension.generated' => ['nullable', 'boolean'],
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
