<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

final readonly class CatalogModificationContextDTO
{
    public function __construct(
        public CatalogManufacturerDTO $manufacturer,
        public CatalogVehicleDTO $vehicle,
        public CatalogModificationDTO $modification,
    ) {}
}
