<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует результат мутации каталога во внешний RabbitMQ-транспорт.
 */
final readonly class RabbitMqCatalogMutationNotificationService implements CatalogMutationNotificationServiceInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат мутации каталога наружу.
     *
     * Шаги:
     * 1) Собрать транспортное RabbitMQ-сообщение.
     * 2) Передать сообщение publisher-адаптеру.
     */
    public function notify(CatalogMutationResultDTO $result): void
    {
        $message = new RabbitMessageDTO(
            name: 'VEHICLES_CATALOG_MUTATION_COMPLETED',
            data: $result->toArray(),
        );
        $this->publisher->publish($message);
    }
}
