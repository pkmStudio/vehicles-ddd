<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;
use Illuminate\Support\Collection;

/**
 * Use case port REST-списка ТС производителя публичного каталога.
 */
interface ListManufacturerVehiclesForCatalogUseCaseInterface
{
    /** @return Collection<int, CatalogVehicleDTO>|null */
    public function execute(int $manufacturerId): ?Collection;
}
