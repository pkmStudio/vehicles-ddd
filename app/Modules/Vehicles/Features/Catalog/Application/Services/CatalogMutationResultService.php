<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;

/**
 * Собирает и публикует унифицированные результаты мутаций каталога.
 */
final readonly class CatalogMutationResultService implements CatalogMutationResultServiceInterface
{
    /**
     * Получает порт публикации результатов catalog mutation.
     *
     * Шаги:
     * 1) Принять notification-порт, скрывающий конкретный транспорт результата.
     * 2) Сохранить порт для всех completed/rejected/failed фабрик результата.
     */
    public function __construct(
        private CatalogMutationNotificationServiceInterface $notifier,
    ) {}

    /**
     * Собирает результат мутации со статусом completed.
     *
     * Шаги:
     * 1) Создать DTO результата мутации.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     */
    public function completed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        ?int $recordId = null,
    ): CatalogMutationResultDTO {
        $result = new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Completed,
            externalId: $externalId,
            recordId: $recordId,
        );

        return $this->notify($result);
    }

    /**
     * Собирает результат мутации со статусом rejected.
     *
     * Шаги:
     * 1) Создать DTO результата мутации.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     */
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
        $result = new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Rejected,
            externalId: $externalId,
            recordId: $recordId,
            reason: $reason->value,
            errors: $errors,
        );

        return $this->notify($result);
    }

    /**
     * Собирает результат мутации со статусом failed.
     *
     * Шаги:
     * 1) Создать DTO результата мутации.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     */
    public function failed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
    ): CatalogMutationResultDTO {
        $result = new CatalogMutationResultDTO(
            userId: $userId,
            operationId: $operationId,
            entity: $entity,
            operation: $operation,
            status: CatalogMutationStatusEnum::Failed,
            externalId: $externalId,
        );

        return $this->notify($result);
    }

    /**
     * Публикует результат мутации каталога наружу.
     *
     * Шаги:
     * 1) Собрать транспортное RabbitMQ-сообщение.
     * 2) Передать сообщение publisher-адаптеру.
     */
    private function notify(CatalogMutationResultDTO $result): CatalogMutationResultDTO
    {
        $this->notifier->notify($result);

        return $result;
    }
}
