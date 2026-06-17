<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMQ\Workers;

use VladimirYuldashev\LaravelQueueRabbitMQ\Horizon\RabbitMQQueue;

/**
 * Кастомная очередь RabbitMQ для интеграции с Horizon.
 * Отключает отправку событий в Horizon для избежания конфликтов
 */
final class CustomRabbitMQQueue extends RabbitMQQueue
{
    /**
     * Переопределяем метод отправки события в Horizon.
     * Оставляем пустым для избежания конфликтов со стандартной обработкой
     *
     * @param  string  $queue
     * @param $event
     */
    protected function event($queue, $event): void {}
}