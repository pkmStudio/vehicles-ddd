<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Vehicle;

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
        public array $vehicle,
    ) {}
}
