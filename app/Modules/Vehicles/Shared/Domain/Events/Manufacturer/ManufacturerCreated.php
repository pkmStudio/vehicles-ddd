<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Manufacturer;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения производителей.
 */
final readonly class ManufacturerCreated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $manufacturer;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $manufacturer
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $manufacturer)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->manufacturer = is_array($manufacturer)
            ? CatalogEventPayloadDTO::fromArray($manufacturer, 'mfa_id')
            : $manufacturer;
    }
}
