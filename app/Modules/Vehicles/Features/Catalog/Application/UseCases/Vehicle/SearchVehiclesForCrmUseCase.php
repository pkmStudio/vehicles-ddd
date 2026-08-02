<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use Illuminate\Support\Collection;

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
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function execute(string $query, int $limit = 20): Collection
    {
        return $this->vehicles->search($query, $limit);
    }
}
