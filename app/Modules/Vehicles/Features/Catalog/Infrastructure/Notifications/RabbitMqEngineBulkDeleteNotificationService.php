<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications\EngineBulkDeleteNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteErrorDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteResultDTO;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\BulkDelete\DTO\VehiclesCatalogBulkDeleteCompleted;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\BulkDelete\DTO\VehiclesCatalogBulkDeleteError;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\VehiclesEventName;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует результат массового удаления двигателей во внешний RabbitMQ-транспорт.
 */
final readonly class RabbitMqEngineBulkDeleteNotificationService implements EngineBulkDeleteNotificationServiceInterface
{
    private const int MAX_INLINE_ERRORS = 100;

    /**
     * Получает RabbitMQ publisher для outbound result events.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат bulk-delete двигателей наружу.
     */
    public function notify(EngineBulkDeleteResultDTO $result): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: VehiclesEventName::VehiclesCatalogBulkDeleteCompleted->value,
            data: new VehiclesCatalogBulkDeleteCompleted(
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
                    static fn (EngineBulkDeleteErrorDTO $error): VehiclesCatalogBulkDeleteError => new VehiclesCatalogBulkDeleteError(
                        id: $error->id,
                        reason: $error->reason,
                        businessKey: $error->businessKey,
                    ),
                    array_slice($result->errors, 0, self::MAX_INLINE_ERRORS),
                ),
            )->toArray(),
        ));
    }
}
