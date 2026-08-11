<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use ValueError;

/**
 * Публикует failed-result для payload, несовместимого с текущим wire-контрактом Warehouse Catalog.
 */
final readonly class WarehouseCatalogMutationContractMismatchReporter
{
    private const string MESSAGE = 'Payload is incompatible with current dan-vehicles contract. Update dan-wire-contracts version.';

    public function __construct(
        private WarehouseCatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $invalidKeys
     */
    public function report(WarehouseCatalogEntityEnum $entity, array $payload, array $invalidKeys): void
    {
        $userId = $this->integerOrNull($payload['user_id'] ?? null);
        $operationId = $this->stringOrNull($payload['operation_id'] ?? null);
        $operation = $this->operationOrNull($payload['operation'] ?? null);

        if ($userId === null || $operationId === null || $operation === null) {
            return;
        }

        $this->notifier->notify(new WarehouseCatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: WarehouseCatalogMutationStatusEnum::Failed,
            recordId: $this->recordId($entity, $payload),
            reason: WarehouseCatalogMutationRejectReasonEnum::ContractMismatch->value,
            errors: [
                'message' => self::MESSAGE,
                'invalid_keys' => $invalidKeys,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordId(WarehouseCatalogEntityEnum $entity, array $payload): ?int
    {
        $entityKey = match ($entity) {
            WarehouseCatalogEntityEnum::Brand => 'brand',
            WarehouseCatalogEntityEnum::Nomenclature => 'nomenclature',
            WarehouseCatalogEntityEnum::PackDimension => 'pack_dimension',
            WarehouseCatalogEntityEnum::Kit => 'kit',
        };

        $entityPayload = $payload[$entityKey] ?? null;

        if (! is_array($entityPayload)) {
            return null;
        }

        return $this->integerOrNull($entityPayload['id'] ?? null);
    }

    private function operationOrNull(mixed $operation): ?WarehouseCatalogMutationOperationEnum
    {
        try {
            return WarehouseCatalogMutationOperationEnum::from((string) $operation);
        } catch (ValueError) {
            return null;
        }
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }
}
