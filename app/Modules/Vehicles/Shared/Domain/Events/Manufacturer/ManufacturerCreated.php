<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Manufacturer;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ManufacturerEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения производителей.
 */
final readonly class ManufacturerCreated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public ManufacturerEventPayloadDTO $manufacturer,
    ) {}
}
