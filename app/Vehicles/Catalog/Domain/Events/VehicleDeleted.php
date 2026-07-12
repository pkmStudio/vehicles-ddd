<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events;

/**
 * Фиксирует доменный факт изменения автомобилей.
 */
final readonly class VehicleDeleted
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $msId,
        public int $vehicleId,
    ) {}
}
