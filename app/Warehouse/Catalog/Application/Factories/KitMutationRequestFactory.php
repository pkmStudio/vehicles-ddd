<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\Factories;

use App\Warehouse\Catalog\Domain\Contracts\Factories\KitMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Собирает DTO запроса мутации Warehouse-набора из валидированного payload.
 */
final readonly class KitMutationRequestFactory implements KitMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации набора из payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): KitMutationRequestDTO
    {
        $operation = WarehouseCatalogMutationOperationEnum::from((string) $payload['operation']);

        return new KitMutationRequestDTO(
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
    private function createRequest(array $payload): CreateKitRequestDTO
    {
        $kit = $payload['kit'];

        return new CreateKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            nomenclatureIds: $this->ids($kit['nomenclature_ids']),
            isSaleSeparately: (bool) ($kit['is_sale_separately'] ?? false),
            isActive: (bool) ($kit['is_active'] ?? true),
            guarantee: (int) ($kit['guarantee'] ?? 12),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdateKitRequestDTO
    {
        $kit = $payload['kit'];

        return new UpdateKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $kit['id'],
            nomenclatureIds: $this->ids($kit['nomenclature_ids']),
            isSaleSeparately: (bool) ($kit['is_sale_separately'] ?? false),
            isActive: (bool) ($kit['is_active'] ?? true),
            guarantee: (int) ($kit['guarantee'] ?? 12),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeleteKitRequestDTO
    {
        return new DeleteKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['kit']['id'],
        );
    }

    /**
     * Нормализует список id номенклатур из payload.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_map(
            fn (mixed $id): int => (int) $id,
            $ids,
        ));
    }
}
