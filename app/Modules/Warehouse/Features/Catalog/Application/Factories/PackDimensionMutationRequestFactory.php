<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories\PackDimensionMutationRequestFactoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Собирает DTO запроса мутации упаковочного размера Warehouse из валидированного payload.
 */
final readonly class PackDimensionMutationRequestFactory implements PackDimensionMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации упаковочного размера из payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): PackDimensionMutationRequestDTO
    {
        $operation = WarehouseCatalogMutationOperationEnum::from((string) $payload['operation']);

        return new PackDimensionMutationRequestDTO(
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
    private function createRequest(array $payload): CreatePackDimensionRequestDTO
    {
        $packDimension = $payload['pack_dimension'];

        return new CreatePackDimensionRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            name: (string) $packDimension['name'],
            weight: (int) $packDimension['weight'],
            width: (int) $packDimension['width'],
            height: (int) $packDimension['height'],
            length: (int) $packDimension['length'],
            price: (int) $packDimension['price'],
            typeId: (int) $packDimension['type_id'],
            generated: (bool) ($packDimension['generated'] ?? false),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdatePackDimensionRequestDTO
    {
        $packDimension = $payload['pack_dimension'];

        return new UpdatePackDimensionRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $packDimension['id'],
            name: (string) $packDimension['name'],
            weight: (int) $packDimension['weight'],
            width: (int) $packDimension['width'],
            height: (int) $packDimension['height'],
            length: (int) $packDimension['length'],
            price: (int) $packDimension['price'],
            typeId: (int) $packDimension['type_id'],
            generated: (bool) ($packDimension['generated'] ?? false),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeletePackDimensionRequestDTO
    {
        return new DeletePackDimensionRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['pack_dimension']['id'],
        );
    }
}
