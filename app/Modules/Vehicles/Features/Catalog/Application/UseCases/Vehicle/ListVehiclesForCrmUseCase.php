<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии списка и справочных options Vehicles.
 */
final readonly class ListVehiclesForCrmUseCase implements ListVehiclesForCrmUseCaseInterface
{
    /**
     * Получает read repository порт Vehicles CRM.
     */
    public function __construct(
        private VehicleCrmReadRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает постраничный список ТС.
     */
    public function execute(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO
    {
        return $this->vehicles->paginate($query);
    }

    /**
     * Возвращает feature options.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection
    {
        return $this->vehicles->featureOptions();
    }

    /**
     * Возвращает feature value options.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection
    {
        return $this->vehicles->featureValueOptions($featureId);
    }

    /**
     * Возвращает detail template options.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection
    {
        return $this->vehicles->detailTemplateOptions();
    }

    /**
     * Возвращает manufacturer options.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->vehicles->manufacturerOptions($query, $id, $limit);
    }
}
