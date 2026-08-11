<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;
use Illuminate\Support\Collection;

interface VehicleCatalogClientInterface
{
    /**
     * @return Collection<int, CatalogManufacturerDTO>
     */
    public function manufacturers(): Collection;

    /**
     * @return null|Collection<int, CatalogVehicleDTO>
     */
    public function vehicles(int $manufacturerId): ?Collection;

    /**
     * @return null|Collection<int, CatalogModificationDTO>
     */
    public function modifications(int $vehicleId): ?Collection;

    public function modification(int $modificationId): ?CatalogModificationContextDTO;
}
