<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Messaging\Validators;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final readonly class ManufacturerMutationPayloadValidator
{
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
            'manufacturer' => ['required', 'array'],
            'manufacturer.mfa_id' => ['required', 'integer'],
        ];

        if ($operation === CatalogMutationOperationEnum::Create->value || $operation === CatalogMutationOperationEnum::Update->value) {
            $rules += [
                'manufacturer.name' => ['required', 'string', 'max:255'],
                'manufacturer.provider' => ['nullable', 'string', Rule::in($this->enumValues(ProviderEnum::cases()))],
            ];
        }

        return $this->validator->make($data, $rules);
    }

    private function operations(): array
    {
        return array_map(fn (CatalogMutationOperationEnum $operation): string => $operation->value, CatalogMutationOperationEnum::cases());
    }

    private function enumValues(array $cases): array
    {
        return array_map(fn (object $case): string => $case->value, $cases);
    }
}
