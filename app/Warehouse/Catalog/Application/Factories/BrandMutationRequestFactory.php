<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\Factories;

use App\Warehouse\Catalog\Domain\Contracts\Factories\BrandMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Собирает DTO запроса мутации Warehouse-бренда из валидированного payload.
 */
final readonly class BrandMutationRequestFactory implements BrandMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации бренда из payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): BrandMutationRequestDTO
    {
        $operation = WarehouseCatalogMutationOperationEnum::from((string) $payload['operation']);

        return new BrandMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                WarehouseCatalogMutationOperationEnum::Create => $this->createRequest($payload),
                WarehouseCatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                WarehouseCatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createRequest(array $payload): CreateBrandRequestDTO
    {
        $brand = $payload['brand'];

        return new CreateBrandRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            name: (string) $brand['name'],
            numberSert: (string) $brand['number_sert'],
            dateStart: (string) $brand['date_start'],
            dateEnd: (string) $brand['date_end'],
            char: isset($brand['char']) ? (string) $brand['char'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdateBrandRequestDTO
    {
        $brand = $payload['brand'];

        return new UpdateBrandRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $brand['id'],
            name: (string) $brand['name'],
            numberSert: (string) $brand['number_sert'],
            dateStart: (string) $brand['date_start'],
            dateEnd: (string) $brand['date_end'],
            char: isset($brand['char']) ? (string) $brand['char'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeleteBrandRequestDTO
    {
        return new DeleteBrandRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['brand']['id'],
        );
    }
}
