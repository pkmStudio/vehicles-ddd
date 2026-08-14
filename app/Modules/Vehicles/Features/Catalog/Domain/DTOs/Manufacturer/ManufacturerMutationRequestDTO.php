<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Передает параметры сценария или результат мутации производителей.
 */
final readonly class ManufacturerMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateManufacturerRequestDTO|UpdateManufacturerRequestDTO|DeleteManufacturerRequestDTO $request,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new self(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => self::createRequest($payload),
                CatalogMutationOperationEnum::Update => self::updateRequest($payload),
                CatalogMutationOperationEnum::Delete => self::deleteRequest($payload),
            },
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function createRequest(array $payload): CreateManufacturerRequestDTO
    {
        $manufacturer = $payload['manufacturer'];

        return new CreateManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $manufacturer['mfa_id'],
            name: (string) $manufacturer['name'],
            provider: ProviderEnum::from((string) $manufacturer['provider']),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function updateRequest(array $payload): UpdateManufacturerRequestDTO
    {
        $manufacturer = $payload['manufacturer'];

        return new UpdateManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $manufacturer['mfa_id'],
            name: (string) $manufacturer['name'],
            provider: ProviderEnum::from((string) $manufacturer['provider']),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function deleteRequest(array $payload): DeleteManufacturerRequestDTO
    {
        return new DeleteManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $payload['manufacturer']['mfa_id'],
        );
    }
}
