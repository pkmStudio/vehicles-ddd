<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\KitBulkDeleteNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteErrorDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteResultDTO;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\BulkDelete\DTO\WarehouseCatalogBulkDeleteCompleted;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\BulkDelete\DTO\WarehouseCatalogBulkDeleteError;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\VehiclesEventName;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует результат массового удаления наборов во внешний RabbitMQ-транспорт.
 */
final readonly class RabbitMqKitBulkDeleteNotificationService implements KitBulkDeleteNotificationServiceInterface
{
    private const int MAX_INLINE_ERRORS = 100;

    /**
     * Получает RabbitMQ publisher для outbound result events.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат bulk-delete наборов наружу.
     */
    public function notify(KitBulkDeleteResultDTO $result): void
    {
        $message = new RabbitMessageDTO(
            name: VehiclesEventName::WarehouseCatalogBulkDeleteCompleted->value,
            data: new WarehouseCatalogBulkDeleteCompleted(
                userId: $result->userId,
                operationId: $result->operationId,
                entity: $result->entity->value,
                status: $result->status->value,
                requested: $result->requested,
                deleted: $result->deleted,
                skipped: $result->skipped,
                failed: $result->failed,
                errorsCount: count($result->errors),
                errorsTruncated: count($result->errors) > self::MAX_INLINE_ERRORS,
                errors: array_map(
                    static fn (KitBulkDeleteErrorDTO $error): WarehouseCatalogBulkDeleteError => new WarehouseCatalogBulkDeleteError(
                        id: $error->id,
                        reason: $error->reason,
                        businessKey: $error->businessKey,
                    ),
                    array_slice($result->errors, 0, self::MAX_INLINE_ERRORS),
                ),
            )->toArray(),
        );

        $this->publisher->publish($message);
    }
}
