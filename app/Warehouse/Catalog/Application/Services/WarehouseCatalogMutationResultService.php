<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\Services;

use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Собирает и публикует унифицированные результаты мутаций Warehouse-каталога.
 */
final readonly class WarehouseCatalogMutationResultService implements WarehouseCatalogMutationResultServiceInterface
{
    /**
     * Инициализирует notification-порт.
     */
    public function __construct(
        private WarehouseCatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * Собирает и публикует completed-результат.
     */
    public function completed(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO {
        $result = new WarehouseCatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: WarehouseCatalogMutationStatusEnum::Completed,
            recordId: $recordId,
            businessKey: $businessKey,
        );

        return $this->notify($result);
    }

    /**
     * Собирает и публикует rejected-результат.
     *
     * @param  array<string, mixed>  $errors
     */
    public function rejected(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        WarehouseCatalogMutationRejectReasonEnum $reason,
        array $errors = [],
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO {
        $result = new WarehouseCatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: WarehouseCatalogMutationStatusEnum::Rejected,
            recordId: $recordId,
            businessKey: $businessKey,
            reason: $reason->value,
            errors: $errors,
        );

        return $this->notify($result);
    }

    /**
     * Собирает и публикует failed-результат.
     */
    public function failed(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO {
        $result = new WarehouseCatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: WarehouseCatalogMutationStatusEnum::Failed,
            recordId: $recordId,
            businessKey: $businessKey,
        );

        return $this->notify($result);
    }

    /**
     * Публикует результат и возвращает тот же DTO вызывающему коду.
     */
    private function notify(WarehouseCatalogMutationResultDTO $result): WarehouseCatalogMutationResultDTO
    {
        $this->notifier->notify($result);

        return $result;
    }
}
