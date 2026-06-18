<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Messaging\Enums;

/**
 * Перечисление входящих событий, которые принимает сервис dan-vehicles.
 * Каждое событие маппится на [Class, Method] обработчик.
 *
 * TODO: объявить события, которые dan-vehicles слушает из общего обменника
 * application.events, и их обработчики. Пример (как было в dan-center):
 *
 *   case REVIEWS_PARSED = 'REVIEWS_PARSED';
 *   ...
 *   self::REVIEWS_PARSED => [ReviewService::class, 'upsert'],
 */
enum InboundEventsEnum: string
{
    /**
     * Возвращает обработчик для события в формате [Class, Method].
     *
     * @return array{0: class-string, 1: string}
     */
    public function getHandler(): array
    {
        return [];
        //     match ($this) {
        //     // TODO: добавить маппинг событий vehicles на сервисы-обработчики.
        // };
    }
}
