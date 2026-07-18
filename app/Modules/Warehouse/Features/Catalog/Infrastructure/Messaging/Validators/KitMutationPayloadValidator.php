<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации Warehouse-набора.
 */
final readonly class KitMutationPayloadValidator
{
    /**
     * Инициализирует фабрику валидаторов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта Kit CRUD.
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
            'kit' => ['required', 'array'],
        ];

        if ($operation === WarehouseCatalogMutationOperationEnum::Update->value
            || $operation === WarehouseCatalogMutationOperationEnum::Delete->value) {
            $rules['kit.id'] = ['required', 'integer', 'min:1'];
        }

        if ($operation === WarehouseCatalogMutationOperationEnum::Create->value
            || $operation === WarehouseCatalogMutationOperationEnum::Update->value) {
            $rules += [
                'kit.nomenclature_ids' => ['required', 'array', 'min:1'],
                'kit.nomenclature_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
                'kit.is_sale_separately' => ['nullable', 'boolean'],
                'kit.is_active' => ['nullable', 'boolean'],
                'kit.guarantee' => ['nullable', 'integer', 'min:0'],
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
