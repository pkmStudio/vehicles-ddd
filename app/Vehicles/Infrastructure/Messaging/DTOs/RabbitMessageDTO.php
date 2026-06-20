<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Messaging\DTOs;

use App\Vehicles\Infrastructure\Messaging\Enums\OutboundEventsEnum;

/**
 * DTO для сообщений RabbitMQ:
 * - name: Тип события (routing key) из OutboundEventsEnum
 * - data: Данные сообщения в виде массива
 */
final readonly class RabbitMessageDTO implements \JsonSerializable
{
    public function __construct(
        public OutboundEventsEnum $name,
        public array $data,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name->name,
            'data' => $this->data,
        ];
    }
}
