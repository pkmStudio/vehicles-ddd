<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\SearchVehiclesForCrmUseCaseInterface;

/**
 * Оркестрирует CRM search-сценарий Vehicles.
 */
final readonly class SearchVehiclesForCrmUseCase implements SearchVehiclesForCrmUseCaseInterface
{
    /**
     * Получает read repository порт Vehicles CRM.
     */
    public function __construct(
        private VehicleCrmReadRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает compact search options.
     */
    public function execute(string $query, int $limit = 20): array
    {
        return $this->vehicles->search($query, $limit);
    }
}
