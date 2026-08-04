<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;

/**
 * Возвращает REST-detail модификации с её ТС и производителем.
 */
final readonly class ShowModificationForCatalogUseCase implements ShowModificationForCatalogUseCaseInterface
{
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    public function execute(int $modificationId): ?CatalogModificationContextDTO
    {
        $modification = $this->modifications->findById($modificationId);

        if ($modification === null) {
            return null;
        }

        $vehicle = $this->vehicles->findById($modification->vehicleId);

        if ($vehicle === null || ! $vehicle->isAllow) {
            return null;
        }

        $manufacturer = $this->manufacturers->findById($vehicle->manufacturerId);

        if ($manufacturer === null) {
            return null;
        }

        return new CatalogModificationContextDTO(
            manufacturer: CatalogManufacturerDTO::fromData($manufacturer),
            vehicle: CatalogVehicleDTO::fromData($vehicle),
            modification: CatalogModificationDTO::fromData($modification),
        );
    }
}
