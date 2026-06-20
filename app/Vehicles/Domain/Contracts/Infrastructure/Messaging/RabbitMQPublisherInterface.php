<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Messaging;

use App\Vehicles\Infrastructure\Messaging\DTOs\RabbitMessageDTO;

interface RabbitMQPublisherInterface
{
    public function publish(RabbitMessageDTO $message, string $routingKey = ''): bool;
}

