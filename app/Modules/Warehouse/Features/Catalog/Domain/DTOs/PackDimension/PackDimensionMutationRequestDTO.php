<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации упаковочного размера Warehouse.
 */
final readonly class PackDimensionMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreatePackDimensionRequestDTO|UpdatePackDimensionRequestDTO|DeletePackDimensionRequestDTO $request,
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
    private static function createRequest(array $payload): CreatePackDimensionRequestDTO
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
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function updateRequest(array $payload): UpdatePackDimensionRequestDTO
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
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function deleteRequest(array $payload): DeletePackDimensionRequestDTO
    {
        return new DeletePackDimensionRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['pack_dimension']['id'],
        );
    }
}
