<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Services;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Собирает и публикует унифицированные результаты мутаций Warehouse-каталога.
 */
final readonly class WarehouseCatalogMutationResultService implements WarehouseCatalogMutationResultServiceInterface
{
    /**
     * Получает notification port для публикации результата catalog mutation.
     *
     * Шаги:
     * 1) Принять WarehouseCatalogMutationNotificationServiceInterface.
     * 2) Использовать port во всех outcome-методах после сборки result DTO.
     */
    public function __construct(
        private WarehouseCatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * Собирает и публикует completed-результат.
     *
     * Шаги:
     * 1) Принять контекст успешной мутации и бизнес-ключ.
     * 2) Собрать DTO результата со статусом completed.
     * 3) Передать результат в notification-сервис и вернуть DTO.
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
     *
     * Шаги:
     * 1) Принять контекст отклонения и причину.
     * 2) Собрать DTO результата со статусом rejected.
     * 3) Передать результат в notification-сервис и вернуть DTO.
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
     *
     * Шаги:
     * 1) Принять контекст технического сбоя мутации.
     * 2) Собрать DTO результата со статусом failed.
     * 3) Отправить уведомление о неуспешном завершении.
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
