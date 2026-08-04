<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список разрешённых ТС производителя.
 */
final readonly class ListManufacturerVehiclesForCatalogUseCase implements ListManufacturerVehiclesForCatalogUseCaseInterface
{
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleRepositoryInterface $vehicles,
    ) {}

    /** @return Collection<int, CatalogVehicleDTO>|null */
    public function execute(int $manufacturerId): ?Collection
    {
        if ($this->manufacturers->findById($manufacturerId) === null) {
            return null;
        }

        return $this->vehicles
            ->findAllowedByManufacturerId($manufacturerId)
            ->map(static fn (VehicleData $vehicle): CatalogVehicleDTO => CatalogVehicleDTO::fromData($vehicle))
            ->values();
    }
}
