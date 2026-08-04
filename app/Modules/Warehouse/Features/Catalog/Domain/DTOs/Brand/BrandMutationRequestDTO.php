<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * DTO общей входящей команды мутации Warehouse-бренда.
 */
final readonly class BrandMutationRequestDTO
{
    /**
     * Хранит тип операции и DTO конкретной операции.
     */
    public function __construct(
        public WarehouseCatalogMutationOperationEnum $operation,
        public CreateBrandRequestDTO|UpdateBrandRequestDTO|DeleteBrandRequestDTO $request,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
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
     * @param  array<string, mixed>  $payload
     */
    private static function createRequest(array $payload): CreateBrandRequestDTO
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
    private static function updateRequest(array $payload): UpdateBrandRequestDTO
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
    private static function deleteRequest(array $payload): DeleteBrandRequestDTO
    {
        return new DeleteBrandRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['brand']['id'],
        );
    }
}
