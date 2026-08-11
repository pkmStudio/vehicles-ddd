<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCrmClient implements VehicleCrmClientInterface
{
    public function __construct(
        private ListVehiclesForCrmUseCaseInterface $listVehicles,
        private ShowVehicleForCrmUseCaseInterface $showVehicle,
        private SearchVehiclesForCrmUseCaseInterface $searchVehicles,
        private ListVehicleCrmOptionsUseCaseInterface $vehicleOptions,
    ) {}

    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO
    {
        return $this->listVehicles->execute($query);
    }

    public function show(int $id): ?VehicleCrmDetailDTO
    {
        return $this->showVehicle->execute($id);
    }

    public function search(string $query, int $limit): Collection
    {
        return $this->searchVehicles->execute(
            query: $query,
            limit: $limit,
        );
    }

    public function features(): Collection
    {
        return $this->vehicleOptions->features();
    }

    public function featureValues(int $featureId): Collection
    {
        return $this->vehicleOptions->featureValues($featureId);
    }

    public function detailTemplates(): Collection
    {
        return $this->vehicleOptions->detailTemplates();
    }

    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->vehicleOptions->manufacturers(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }
}
