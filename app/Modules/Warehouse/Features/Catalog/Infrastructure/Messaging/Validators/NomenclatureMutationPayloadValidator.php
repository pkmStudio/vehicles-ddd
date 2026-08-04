<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Validators;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Собирает Laravel-валидатор payload мутации Warehouse-номенклатуры.
 */
final readonly class NomenclatureMutationPayloadValidator
{
    /**
     * Инициализирует фабрику валидаторов Laravel.
     */
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    /**
     * Создаёт validator с правилами wire-контракта Nomenclature CRUD.
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
            'nomenclature' => ['required', 'array'],
        ];

        if ($operation === WarehouseCatalogMutationOperationEnum::Update->value
            || $operation === WarehouseCatalogMutationOperationEnum::Delete->value) {
            $rules['nomenclature.id'] = ['required', 'integer', 'min:1'];
        }

        if ($operation === WarehouseCatalogMutationOperationEnum::Create->value
            || $operation === WarehouseCatalogMutationOperationEnum::Update->value) {
            $rules += [
                'nomenclature.type_id' => ['required', 'integer', 'min:1'],
                'nomenclature.brand_id' => ['required', 'integer', 'min:1'],
                'nomenclature.name' => ['required', 'string', 'max:255'],
                'nomenclature.country' => ['required', 'string', 'max:255'],
                'nomenclature.part_number' => ['required', 'string', 'max:255'],
                'nomenclature.color' => ['required', 'string', 'max:255'],
                'nomenclature.weight' => ['required', 'integer', 'min:1'],
                'nomenclature.material' => ['required', 'array', 'min:1'],
                'nomenclature.material.*' => ['string', 'max:255'],
                'nomenclature.vehicle_type' => ['required', 'array', 'min:1'],
                'nomenclature.vehicle_type.*' => ['string', 'max:255'],
                'nomenclature.quantity_pak' => ['required', 'integer', 'min:1'],
                'nomenclature.quantity_in_pak' => ['required', 'integer', 'min:1'],
                'nomenclature.details' => ['present', 'array'],
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
