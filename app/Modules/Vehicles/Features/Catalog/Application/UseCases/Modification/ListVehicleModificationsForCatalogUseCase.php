<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список модификаций разрешённого ТС.
 */
final readonly class ListVehicleModificationsForCatalogUseCase implements ListVehicleModificationsForCatalogUseCaseInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
    ) {}

    /** @return Collection<int, CatalogModificationDTO>|null */
    public function execute(int $vehicleId): ?Collection
    {
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null || ! $vehicle->isAllow) {
            return null;
        }

        return $this->modifications
            ->findByVehicleId($vehicleId)
            ->map(static fn (ModificationData $modification): CatalogModificationDTO => CatalogModificationDTO::fromData($modification))
            ->values();
    }
}
