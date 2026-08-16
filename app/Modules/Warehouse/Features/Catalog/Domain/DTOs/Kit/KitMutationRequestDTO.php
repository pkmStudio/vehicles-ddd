<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-набора.
 */
final readonly class KitMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateKitRequestDTO|UpdateKitRequestDTO|DeleteKitRequestDTO $request,
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
    private static function createRequest(array $payload): CreateKitRequestDTO
    {
        $kit = $payload['kit'];

        return new CreateKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            nomenclatureIds: self::ids($kit['nomenclature_ids']),
            guarantee: (int) $kit['guarantee'],
            isSaleSeparately: (bool) ($kit['is_sale_separately'] ?? false),
            isActive: (bool) ($kit['is_active'] ?? true),
        );
    }

    /**
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function updateRequest(array $payload): UpdateKitRequestDTO
    {
        $kit = $payload['kit'];

        return new UpdateKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $kit['id'],
            nomenclatureIds: self::ids($kit['nomenclature_ids']),
            guarantee: (int) $kit['guarantee'],
            isSaleSeparately: (bool) ($kit['is_sale_separately'] ?? false),
            isActive: (bool) ($kit['is_active'] ?? true),
        );
    }

    /**
     * @param  array<string, mixed>  $данные  сообщения
     */
    private static function deleteRequest(array $payload): DeleteKitRequestDTO
    {
        return new DeleteKitRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['kit']['id'],
        );
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private static function ids(array $ids): array
    {
        $toIntegerId = fn (int|string $id): int => (int) $id;

        return array_values(array_map($toIntegerId, $ids));
    }
}
