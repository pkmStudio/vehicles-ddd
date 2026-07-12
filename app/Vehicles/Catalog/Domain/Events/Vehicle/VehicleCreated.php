<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Vehicle;

use App\Vehicles\Catalog\Domain\ModelData\VehicleData;

/**
 * Фиксирует доменный факт изменения автомобилей.
 */
final readonly class VehicleCreated
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public VehicleData $vehicle,
    ) {}
}
