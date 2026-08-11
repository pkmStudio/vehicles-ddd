<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

interface VehicleCrmClientInterface
{
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    public function show(int $id): ?VehicleCrmDetailDTO;

    /**
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit): Collection;

    /**
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection;

    /**
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection;

    /**
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection;

    /**
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
