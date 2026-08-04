<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

/**
 * Публичный REST-контекст модификации с её ТС и производителем.
 */
final readonly class CatalogModificationContextDTO
{
    public function __construct(
        public CatalogManufacturerDTO $manufacturer,
        public CatalogVehicleDTO $vehicle,
        public CatalogModificationDTO $modification,
    ) {}

    /** @return array<string, array<string, float|int|string|null>> */
    public function toArray(): array
    {
        return [
            'manufacturer' => $this->manufacturer->toArray(),
            'vehicle' => $this->vehicle->toArray(),
            'modification' => $this->modification->toArray(),
        ];
    }
}
