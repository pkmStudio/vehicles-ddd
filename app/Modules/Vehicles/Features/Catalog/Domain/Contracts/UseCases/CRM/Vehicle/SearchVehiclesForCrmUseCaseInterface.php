<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use Illuminate\Support\Collection;

/**
 * Use case port compact search CRM API Vehicles.
 */
interface SearchVehiclesForCrmUseCaseInterface
{
    /**
     * Возвращает compact search options автомобилей.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function execute(string $query, int $limit = 20): Collection;
}
