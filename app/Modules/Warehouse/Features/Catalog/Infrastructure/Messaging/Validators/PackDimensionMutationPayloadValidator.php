<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации упаковочного размера Warehouse.
 */
final readonly class PackDimensionMutationPayloadValidator
{
    /**
     * Инициализирует фабрику валидаторов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта PackDimension CRUD.
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
        return array_map(
            fn (WarehouseCatalogMutationOperationEnum $operation): string => $operation->value,
            WarehouseCatalogMutationOperationEnum::cases(),
        );
    }
}
