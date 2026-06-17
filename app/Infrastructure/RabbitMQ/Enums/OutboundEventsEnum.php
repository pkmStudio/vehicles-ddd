<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMQ\Enums;

/**
 * Перечисление исходящих событий, которые отправляются из dan-vehicles.
 * Значение enum используется как routing key для RabbitMQ.
 *
 * event => routing key
 *
 * TODO: объявить события, которые публикует dan-vehicles, напр.:
 *   case VEHICLE_UPSERTED = 'vehicles.vehicle.upserted';
 */
enum OutboundEventsEnum: string
{
}
