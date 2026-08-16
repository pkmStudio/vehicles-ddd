<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\KitResetNotificationServiceInterface;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\OperationStatus;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\VehiclesEventName;
use PkmStudio\DanWireContracts\Vehicles\Shared\Results\DTO\CatalogMutationCompleted;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует результат bulk-сброса наборов в общий result event Warehouse-каталога.
 */
final readonly class RabbitMqKitResetNotificationService implements KitResetNotificationServiceInterface
{
    private const string ENTITY = 'kit';

    private const string OPERATION = 'reset';

    private const string BUSINESS_KEY = 'reset';

    /**
     * Получает RabbitMQ publisher для исходящих result events.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует successful result для сброса наборов.
     */
    public function completed(int $userId, string $operationId): void
    {
        $this->publish($userId, $operationId, OperationStatus::Completed);
    }

    /**
     * Публикует failed result для сброса наборов.
     */
    public function failed(int $userId, string $operationId): void
    {
        $this->publish($userId, $operationId, OperationStatus::Failed);
    }

    /**
     * Собирает wire DTO результата и публикует его в RabbitMQ.
     */
    private function publish(int $userId, string $operationId, OperationStatus $status): void
    {
        $result = CatalogMutationCompleted::fromArray([
            'user_id' => $userId,
            'operation_id' => $operationId,
            'entity' => self::ENTITY,
            'operation' => self::OPERATION,
            'status' => $status->value,
            'business_key' => self::BUSINESS_KEY,
        ]);

        $message = new RabbitMessageDTO(
            name: VehiclesEventName::WarehouseCatalogMutationCompleted->value,
            data: $result->toArray(),
        );

        $this->publisher->publish($message);
    }
}
