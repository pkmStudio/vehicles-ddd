<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Services;

use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationStatusEnum;

final readonly class CatalogMutationResultService implements CatalogMutationResultServiceInterface
{
    public function __construct(
        private CatalogMutationNotificationServiceInterface $notifier,
    ) {}

    public function completed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        ?int $recordId = null,
    ): CatalogMutationResultDTO {
        return $this->notify(new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Completed,
            externalId: $externalId,
            recordId: $recordId,
        ));
    }

    public function rejected(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        CatalogMutationRejectReasonEnum $reason,
        array $errors = [],
        ?int $recordId = null,
    ): CatalogMutationResultDTO {
        return $this->notify(new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Rejected,
            externalId: $externalId,
            recordId: $recordId,
            reason: $reason->value,
            errors: $errors,
        ));
    }

    public function failed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
    ): CatalogMutationResultDTO {
        return $this->notify(new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Failed,
            externalId: $externalId,
        ));
    }

    private function notify(CatalogMutationResultDTO $result): CatalogMutationResultDTO
    {
        $this->notifier->notify($result);

        return $result;
    }
}
