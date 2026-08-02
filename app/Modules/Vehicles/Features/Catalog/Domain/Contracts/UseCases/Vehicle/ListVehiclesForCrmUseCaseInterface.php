<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Use case port списка и options CRM API Vehicles.
 */
interface ListVehiclesForCrmUseCaseInterface
{
    /**
     * Возвращает постраничный список ТС для CRM.
     */
    public function execute(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    /**
     * Возвращает feature options.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection;

    /**
     * Возвращает feature value options.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection;

    /**
     * Возвращает detail template options.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection;

    /**
     * Возвращает manufacturer options.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
