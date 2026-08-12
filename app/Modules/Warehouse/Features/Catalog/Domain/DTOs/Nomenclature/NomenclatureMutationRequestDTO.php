<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-номенклатуры.
 */
final readonly class NomenclatureMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateNomenclatureRequestDTO|UpdateNomenclatureRequestDTO|DeleteNomenclatureRequestDTO $request,
    ) {}

    /**
     * @param  array<string, mixed>  $данные  сообщения
     */
    public static function fromArray(array $payload): self
    {
        $operation = WarehouseCatalogMutationOperationEnum::from((string) $payload['operation']);

        return new self(
            operation: $operation,
            request: match ($operation) {
                WarehouseCatalogMutationOperationEnum::Create => self::createRequest($payload),
                WarehouseCatalogMutationOperationEnum::Update => self::updateRequest($payload),
                WarehouseCatalogMutationOperationEnum::Delete => self::deleteRequest($payload),
            },
        );
    }

    /**
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function createRequest(array $payload): CreateNomenclatureRequestDTO
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
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function updateRequest(array $payload): UpdateNomenclatureRequestDTO
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
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function deleteRequest(array $payload): DeleteNomenclatureRequestDTO
    {
        return new DeleteNomenclatureRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['nomenclature']['id'],
        );
    }
}
