<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Vehicle;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\VehicleEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения автомобилей.
 */
final readonly class VehicleCreated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public VehicleEventPayloadDTO $vehicle,
    ) {}
}
