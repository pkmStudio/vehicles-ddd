<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует результат мутации Warehouse-каталога во внешний RabbitMQ-транспорт.
 */
final readonly class RabbitMqWarehouseCatalogMutationNotificationService implements WarehouseCatalogMutationNotificationServiceInterface
{
    /**
     * Инициализирует RabbitMQ publisher.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат мутации Warehouse-каталога наружу.
     */
    public function notify(WarehouseCatalogMutationResultDTO $result): void
    {
        $message = new RabbitMessageDTO(
            name: 'WAREHOUSE_CATALOG_MUTATION_COMPLETED',
            data: $result->toArray(),
        );
        $this->publisher->publish($message);
    }
}
