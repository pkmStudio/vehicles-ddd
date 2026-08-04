<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии справочных options Vehicles.
 */
final readonly class ListVehicleCrmOptionsUseCase implements ListVehicleCrmOptionsUseCaseInterface
{
    /**
     * Получает repository-порт Vehicles CRM.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

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
        return collect([
            new VehicleCrmDetailTemplateOptionDTO(
                id: 'wiper',
                label: 'Щетки стеклоочистителя',
            ),
        ]);
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
