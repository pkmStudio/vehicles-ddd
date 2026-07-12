<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Factories;

use App\Vehicles\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class ManufacturerMutationRequestFactory implements ManufacturerMutationRequestFactoryInterface
{
    public function make(array $payload): ManufacturerMutationRequestDTO
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new ManufacturerMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => $this->createRequest($payload),
                CatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                CatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    private function createRequest(array $payload): CreateManufacturerRequestDTO
    {
        $manufacturer = $payload['manufacturer'];

        return new CreateManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $manufacturer['mfa_id'],
            name: (string) $manufacturer['name'],
            provider: isset($manufacturer['provider']) ? ProviderEnum::from((string) $manufacturer['provider']) : ProviderEnum::OD,
        );
    }

    private function updateRequest(array $payload): UpdateManufacturerRequestDTO
    {
        $manufacturer = $payload['manufacturer'];

        return new UpdateManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $manufacturer['mfa_id'],
            name: (string) $manufacturer['name'],
            provider: isset($manufacturer['provider']) ? ProviderEnum::from((string) $manufacturer['provider']) : ProviderEnum::OD,
        );
    }

    private function deleteRequest(array $payload): DeleteManufacturerRequestDTO
    {
        return new DeleteManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $payload['manufacturer']['mfa_id'],
        );
    }
}
