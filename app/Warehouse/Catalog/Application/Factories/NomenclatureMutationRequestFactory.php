<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\Factories;

use App\Warehouse\Catalog\Domain\Contracts\Factories\NomenclatureMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Собирает DTO запроса мутации Warehouse-номенклатуры из валидированного payload.
 */
final readonly class NomenclatureMutationRequestFactory implements NomenclatureMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации номенклатуры из payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): NomenclatureMutationRequestDTO
    {
        $operation = WarehouseCatalogMutationOperationEnum::from((string) $payload['operation']);

        return new NomenclatureMutationRequestDTO(
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
    private function createRequest(array $payload): CreateNomenclatureRequestDTO
    {
        $nomenclature = $payload['nomenclature'];

        return new CreateNomenclatureRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            typeId: (int) $nomenclature['type_id'],
            brandId: (int) $nomenclature['brand_id'],
            name: (string) $nomenclature['name'],
            country: (string) $nomenclature['country'],
            partNumber: (string) $nomenclature['part_number'],
            color: (string) $nomenclature['color'],
            weight: (int) $nomenclature['weight'],
            material: array_values($nomenclature['material'] ?? []),
            vehicleType: array_values($nomenclature['vehicle_type'] ?? []),
            quantityPak: (int) $nomenclature['quantity_pak'],
            quantityInPak: (int) $nomenclature['quantity_in_pak'],
            details: $nomenclature['details'] ?? [],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdateNomenclatureRequestDTO
    {
        $nomenclature = $payload['nomenclature'];

        return new UpdateNomenclatureRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $nomenclature['id'],
            typeId: (int) $nomenclature['type_id'],
            brandId: (int) $nomenclature['brand_id'],
            name: (string) $nomenclature['name'],
            country: (string) $nomenclature['country'],
            partNumber: (string) $nomenclature['part_number'],
            color: (string) $nomenclature['color'],
            weight: (int) $nomenclature['weight'],
            material: array_values($nomenclature['material'] ?? []),
            vehicleType: array_values($nomenclature['vehicle_type'] ?? []),
            quantityPak: (int) $nomenclature['quantity_pak'],
            quantityInPak: (int) $nomenclature['quantity_in_pak'],
            details: $nomenclature['details'] ?? [],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeleteNomenclatureRequestDTO
    {
        return new DeleteNomenclatureRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['nomenclature']['id'],
        );
    }
}
