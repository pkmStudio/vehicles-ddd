<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCatalogClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCatalogClient implements VehicleCatalogClientInterface
{
    public function __construct(
        private ListManufacturersForCatalogUseCaseInterface $listManufacturers,
        private ListManufacturerVehiclesForCatalogUseCaseInterface $listVehicles,
        private ListVehicleModificationsForCatalogUseCaseInterface $listModifications,
        private ShowModificationForCatalogUseCaseInterface $showModification,
    ) {}

    public function manufacturers(): Collection
    {
        return $this->listManufacturers->execute();
    }

    public function vehicles(int $manufacturerId): ?Collection
    {
        return $this->listVehicles->execute($manufacturerId);
    }

    public function modifications(int $vehicleId): ?Collection
    {
        return $this->listModifications->execute($vehicleId);
    }

    public function modification(int $modificationId): ?CatalogModificationContextDTO
    {
        return $this->showModification->execute($modificationId);
    }
}
