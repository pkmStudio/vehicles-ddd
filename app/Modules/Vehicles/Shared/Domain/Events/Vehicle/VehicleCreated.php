<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Vehicle;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения автомобилей.
 */
final readonly class VehicleCreated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $vehicle;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $vehicle
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $vehicle)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->vehicle = is_array($vehicle)
            ? CatalogEventPayloadDTO::fromArray($vehicle, 'ms_id')
            : $vehicle;
    }
}
