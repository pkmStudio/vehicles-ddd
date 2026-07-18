<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Собирает DTO запроса мутации производителей из валидированного payload.
 */
final readonly class ManufacturerMutationRequestFactory implements ManufacturerMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации производителей из payload.
     *
     * Шаги:
     * 1) Прочитать тип операции из payload.
     * 2) Собрать конкретный DTO запроса операции.
     * 3) Вернуть общий DTO мутации с операцией и request.
     */
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

    /**
     * Собирает DTO конкретной операции производителей из общего DTO или payload.
     */
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

    /**
     * Собирает DTO конкретной операции производителей из общего DTO или payload.
     */
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

    /**
     * Удаляет запись производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(array $payload): DeleteManufacturerRequestDTO
    {
        return new DeleteManufacturerRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            mfaId: (int) $payload['manufacturer']['mfa_id'],
        );
    }
}
