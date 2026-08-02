<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

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
    public function execute(VehicleCrmReadQueryDTO $query): array
    {
        return $this->vehicles->paginate($query);
    }

    /**
     * Возвращает feature options.
     */
    public function features(): array
    {
        return $this->vehicles->featureOptions();
    }

    /**
     * Возвращает feature value options.
     */
    public function featureValues(int $featureId): array
    {
        return $this->vehicles->featureValueOptions($featureId);
    }

    /**
     * Возвращает detail template options.
     */
    public function detailTemplates(): array
    {
        return $this->vehicles->detailTemplateOptions();
    }
}
