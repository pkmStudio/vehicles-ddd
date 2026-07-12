<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events;

use App\Vehicles\Catalog\Domain\ModelData\VehicleData;

final readonly class VehicleUpdated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public VehicleData $vehicle,
    ) {}
}
