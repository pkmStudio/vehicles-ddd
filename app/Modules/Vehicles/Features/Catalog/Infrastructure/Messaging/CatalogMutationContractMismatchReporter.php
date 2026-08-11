<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use ValueError;

/**
 * Публикует failed-result для payload, несовместимого с текущим wire-контрактом Vehicles Catalog.
 */
final readonly class CatalogMutationContractMismatchReporter
{
    private const string MESSAGE = 'Payload is incompatible with current dan-vehicles contract. Update dan-wire-contracts version.';

    public function __construct(
        private CatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $invalidKeys
     */
    public function report(CatalogEntityEnum $entity, array $payload, array $invalidKeys): void
    {
        $userId = $this->integerOrNull($payload['user_id'] ?? null);
        $operationId = $this->stringOrNull($payload['operation_id'] ?? null);
        $operation = $this->operationOrNull($payload['operation'] ?? null);

        if ($userId === null || $operationId === null || $operation === null) {
            return;
        }

        $this->notifier->notify(new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Failed,
            externalId: $this->externalId($entity, $payload),
            reason: CatalogMutationRejectReasonEnum::ContractMismatch->value,
            errors: [
                'message' => self::MESSAGE,
                'invalid_keys' => $invalidKeys,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function externalId(CatalogEntityEnum $entity, array $payload): ?int
    {
        $entityKey = match ($entity) {
            CatalogEntityEnum::Vehicle => 'vehicle',
            CatalogEntityEnum::Manufacturer => 'manufacturer',
            CatalogEntityEnum::Engine => 'engine',
            CatalogEntityEnum::Modification => 'modification',
            CatalogEntityEnum::PartSpecification => 'part_specification',
        };

        $idKey = match ($entity) {
            CatalogEntityEnum::Vehicle => 'ms_id',
            CatalogEntityEnum::Manufacturer => 'mfa_id',
            CatalogEntityEnum::Engine => 'eng_id',
            CatalogEntityEnum::Modification => 'mod_id',
            CatalogEntityEnum::PartSpecification => 'id',
        };

        $entityPayload = $payload[$entityKey] ?? null;

        if (! is_array($entityPayload)) {
            return null;
        }

        return $this->integerOrNull($entityPayload[$idKey] ?? null);
    }

    private function operationOrNull(mixed $operation): ?CatalogMutationOperationEnum
    {
        try {
            return CatalogMutationOperationEnum::from((string) $operation);
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
