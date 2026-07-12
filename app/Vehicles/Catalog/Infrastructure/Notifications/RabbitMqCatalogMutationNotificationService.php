<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Notifications;

use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqCatalogMutationNotificationService implements CatalogMutationNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notify(CatalogMutationResultDTO $result): void
    {
        $message = new RabbitMessageDTO(
            name: 'CATALOG_MUTATION_COMPLETED',
            data: $result->toArray(),
        );
        $this->publisher->publish($message);
    }
}
